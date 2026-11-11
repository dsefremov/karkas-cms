import { test, expect } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

test.describe('Stage 7.5 blog admin fixes', () => {
  test('reauth modal after reload when session storage cleared', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('admin-login').click();
    await expect(page.getByTestId('admin-authenticated')).toBeVisible();

    await page.evaluate(() => sessionStorage.removeItem('karkas.admin.reauth'));
    await page.goto('/admin/modules');
    await expect(page.getByTestId('admin-authenticated')).toBeVisible();

    const enable = page.getByTestId('enable-blog');
    if (await enable.isEnabled()) {
      await enable.click();
      await expect(page.getByTestId('reauth-modal-backdrop')).toBeVisible();
      await page.getByTestId('reauth-password').fill(ADMIN_PASSWORD);
      await page.getByTestId('reauth-confirm').click();
      await expect(page.getByTestId('reauth-modal-backdrop')).toBeHidden({ timeout: 15_000 });
    } else {
      await page.getByTestId('install-blog').click({ trial: true }).catch(() => undefined);
      await page.getByTestId('installer-run').click({ trial: true }).catch(() => undefined);
      await page.getByTestId('scan-incoming').click();
      await expect(page.getByTestId('reauth-modal-backdrop')).toBeVisible();
    }
  });

  test('wizard hints explain blocked install blog step', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('admin-login').click();
    await page.goto('/admin/modules');
    await expect(page.getByTestId('install-blog-step-hint')).toBeVisible();
    const hint = await page.getByTestId('install-blog-step-hint').innerText();
    expect(hint.length).toBeGreaterThan(10);
    const installBtn = page.getByTestId('install-blog');
    const disabled = await installBtn.isDisabled();
    if (disabled) {
      expect(hint).toMatch(/Core|войдите|уже выполнен/i);
    }
  });
});
