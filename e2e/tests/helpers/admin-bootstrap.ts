import { expect, type Page } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

function authHeaders(): Record<string, string> {
  return {
    'Content-Type': 'application/json',
    'X-Karkas-Reauth': ADMIN_PASSWORD,
  };
}

async function ensureApiSession(page: Page): Promise<void> {
  const login = await page.request.post('/api/v1/admin/session', {
    headers: { 'Content-Type': 'application/json' },
    data: { password: ADMIN_PASSWORD },
  });
  expect(login.ok(), await login.text()).toBeTruthy();
}

async function blogCurrentVersion(page: Page): Promise<string | null> {
  const versions = await page.request.get('/api/v1/platform/versions');
  expect(versions.ok(), await versions.text()).toBeTruthy();
  const json = (await versions.json()) as { packages?: Array<{ package_id: string; current?: string | null }> };
  const blog = json.packages?.find((row) => row.package_id === 'karkas/blog');

  return blog?.current ?? null;
}

async function coreCurrentVersion(page: Page): Promise<string | null> {
  const versions = await page.request.get('/api/v1/platform/versions');
  expect(versions.ok(), await versions.text()).toBeTruthy();
  const json = (await versions.json()) as { packages?: Array<{ package_id: string; current?: string | null }> };
  const core = json.packages?.find((row) => row.package_id === 'karkas/core');

  return core?.current ?? null;
}

async function installBlogFromCatalog(page: Page): Promise<void> {
  const scan = await page.request.post('/api/v1/admin/packages/scan-incoming', { headers: authHeaders() });
  expect(scan.ok(), await scan.text()).toBeTruthy();

  const install = await page.request.post('/api/v1/platform/install', {
    headers: authHeaders(),
    data: { modules: ['karkas/blog'] },
  });
  const status = install.status();
  expect([202, 409].includes(status), await install.text()).toBeTruthy();

  const current = await blogCurrentVersion(page);
  expect(current ?? '').toContain('1.0.0');
}

export async function ensureAdminLogin(page: Page): Promise<void> {
  await page.goto('/admin/login');
  await expect(page.getByTestId('admin-shell')).toBeVisible();
  await ensureApiSession(page);

  await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
  if (await page.getByTestId('admin-login').isVisible()) {
    await page.getByTestId('admin-login').click();
  }
  await expect(page.getByTestId('admin-authenticated')).toBeAttached({ timeout: 20_000 });
}

export async function bootstrapBlogAdmin(page: Page): Promise<void> {
  await ensureAdminLogin(page);
  await page.goto('/admin/dashboard');
  await expect(page.getByTestId('admin-shell')).toBeVisible();

  let needsInstaller = true;
  const installerStatus = await page.request.get('/api/v1/installer/status');
  if (installerStatus.ok()) {
    const statusJson = (await installerStatus.json()) as { status?: string };
    needsInstaller = statusJson.status !== 'completed';
  }
  if (needsInstaller) {
    const bootstrap = await page.request.post('/api/v1/installer/bootstrap', { headers: authHeaders(), data: '{}' });
    const complete = await page.request.post('/api/v1/installer/complete', { headers: authHeaders(), data: '{}' });
    expect(bootstrap.ok(), await bootstrap.text()).toBeTruthy();
    expect(complete.ok(), await complete.text()).toBeTruthy();
  }
  const coreCurrent = await coreCurrentVersion(page);
  expect(coreCurrent ?? '').toMatch(/\d+\.\d+\.\d+/);

  const blogCurrent = await blogCurrentVersion(page);
  if (!(blogCurrent ?? '').includes('1.0.0')) {
    await installBlogFromCatalog(page);
    await page.goto('/admin/modules');
    await expect(page.getByTestId('version-row-karkas-blog').locator('td').nth(1)).toContainText('1.0.0');
  }

  const blogProbe = await page.request.get('/blog');
  const blogHtml = await blogProbe.text();
  const blogReady = blogProbe.ok() && !blogHtml.includes('not enabled');
  if (!blogReady) {
    const enable = await page.request.post('/api/v1/sites/default/modules/karkas/blog/enable', {
      headers: authHeaders(),
      data: { site_label: 'Каркас — демо' },
    });
    expect(enable.ok(), await enable.text()).toBeTruthy();
    await expect
      .poll(async () => {
        const response = await page.request.get('/blog');
        if (!response.ok()) {
          return false;
        }
        const html = await response.text();
        return !html.includes('not enabled');
      })
      .toBe(true);
    await page.reload();
  }
}
