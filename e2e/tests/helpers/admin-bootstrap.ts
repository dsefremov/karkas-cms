import { expect, type Page } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

function authHeaders(): Record<string, string> {
  return {
    'Content-Type': 'application/json',
    'X-Karkas-Reauth': ADMIN_PASSWORD,
  };
}

export async function bootstrapBlogAdmin(page: Page): Promise<void> {
  await page.goto('/admin/');
  await expect(page.getByTestId('admin-shell')).toBeVisible();
  await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
  if (await page.getByTestId('admin-authenticated').isHidden()) {
    await page.getByTestId('admin-login').click();
    await expect(page.getByTestId('admin-authenticated')).toBeVisible();
  }

  let coreCurrent = await page.getByTestId('version-row-karkas-core').locator('td').nth(1).textContent();
  if (!(coreCurrent ?? '').match(/\d+\.\d+\.\d+/)) {
    const bootstrap = await page.request.post('/api/v1/installer/bootstrap', { headers: authHeaders(), data: '{}' });
    const complete = await page.request.post('/api/v1/installer/complete', { headers: authHeaders(), data: '{}' });
    expect(bootstrap.ok()).toBeTruthy();
    expect(complete.ok()).toBeTruthy();
    await page.reload();
    coreCurrent = await page.getByTestId('version-row-karkas-core').locator('td').nth(1).textContent();
    expect(coreCurrent ?? '').toMatch(/\d+\.\d+\.\d+/);
  }

  let blogCurrent = await page.getByTestId('version-row-karkas-blog').locator('td').nth(1).textContent();
  if (!(blogCurrent ?? '').includes('1.0.0')) {
    const install = await page.request.post('/api/v1/platform/install', {
      headers: authHeaders(),
      data: { modules: ['karkas/blog'] },
    });
    expect(install.ok()).toBeTruthy();
    await page.reload();
    blogCurrent = await page.getByTestId('version-row-karkas-blog').locator('td').nth(1).textContent();
    expect(blogCurrent ?? '').toContain('1.0.0');
  }

  const blogProbe = await page.request.get('/blog');
  const blogHtml = await blogProbe.text();
  const blogReady = blogProbe.ok() && !blogHtml.includes('not enabled');
  if (!blogReady) {
    const enable = await page.request.post('/api/v1/sites/default/modules/karkas/blog/enable', {
      headers: authHeaders(),
      data: { site_label: 'Каркас — демо' },
    });
    expect(enable.ok()).toBeTruthy();
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
