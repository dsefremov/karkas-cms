import { test, expect } from '@playwright/test';
import { ensureAdminLogin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

test.describe('Stage 7.5 blog wizard UI', () => {
  test('install and enable blog from modules screen', async ({ page }) => {
    await ensureAdminLogin(page);
    await gotoAdminPath(page, 'modules');

    const installer = page.getByTestId('installer-run');
    if (await installer.isEnabled()) {
      await installer.click();
      await expect(page.getByTestId('admin-message')).toContainText(/Core|установщик/i, { timeout: 90_000 });
    }

    const installBlog = page.getByTestId('install-blog');
    if (await installBlog.isEnabled()) {
      await installBlog.click();
      await expect(page.getByTestId('admin-message')).toContainText(/blog/i, { timeout: 90_000 });
    }

    await expect(page.getByTestId('version-row-karkas-blog')).toBeVisible({ timeout: 30_000 });

    const enable = page.getByTestId('enable-blog');
    if (await enable.isEnabled()) {
      await enable.click();
      if (await page.getByTestId('reauth-modal-backdrop').isVisible()) {
        await page.getByTestId('reauth-password').fill(ADMIN_PASSWORD);
        await page.getByTestId('reauth-confirm').click();
        await expect(page.getByTestId('reauth-modal-backdrop')).toBeHidden({ timeout: 15_000 });
        await enable.click();
      }
      await expect(page.getByTestId('admin-message')).toContainText(/Blog включён|blog уже/i, { timeout: 90_000 });
    }

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
  });
});
