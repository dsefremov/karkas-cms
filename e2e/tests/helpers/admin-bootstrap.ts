import { expect, type Page } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

export async function bootstrapBlogAdmin(page: Page): Promise<void> {
  await page.goto('/admin/');
  await expect(page.getByTestId('admin-shell')).toBeVisible();
  await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
  if (await page.getByTestId('admin-authenticated').isHidden()) {
    await page.getByTestId('admin-login').click();
    await expect(page.getByTestId('admin-authenticated')).toBeVisible();
  }

  const coreCurrent = await page.getByTestId('version-row-karkas-core').locator('td').nth(1).textContent();
  if (!(coreCurrent ?? '').match(/\d+\.\d+\.\d+/)) {
    await page.getByTestId('installer-run').click();
    await expect(page.getByTestId('version-row-karkas-core').locator('td').nth(1)).toContainText(/\d+\.\d+\.\d+/, {
      timeout: 60_000,
    });
  }

  const blogCurrent = await page.getByTestId('version-row-karkas-blog').locator('td').nth(1).textContent();
  if (!(blogCurrent ?? '').includes('1.0.0')) {
    await page.getByTestId('install-blog').click();
    await expect(page.getByTestId('version-row-karkas-blog').locator('td').nth(1)).toContainText('1.0.0', {
      timeout: 60_000,
    });
  }

  const blogProbe = await page.request.get('/blog');
  const blogHtml = await blogProbe.text();
  const blogReady = blogProbe.ok() && !blogHtml.includes('not enabled');
  if (!blogReady) {
    await page.getByTestId('enable-blog').click();
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
  }
}
