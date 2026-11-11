import { test, expect } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

test.describe('Stage 7.5 blog admin fixes', () => {
  test('reauth modal after reload when session storage cleared', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('admin-login').click();
    await expect(page.getByTestId('admin-authenticated')).toBeAttached();

    const siteKey = `reauth-${Date.now()}`;
    const create = await page.request.post('/api/v1/sites', {
      headers: {
        'Content-Type': 'application/json',
        'X-Karkas-Reauth': ADMIN_PASSWORD,
      },
      data: {
        site_key: siteKey,
        label: 'Reauth E2E',
        canonical_host: `${siteKey}.example.test`,
      },
    });
    expect(create.ok(), await create.text()).toBeTruthy();

    await page.evaluate(() => sessionStorage.removeItem('karkas.admin.reauth'));
    await page.reload();
    await expect(page.getByTestId('admin-authenticated')).toBeAttached();

    await page.goto('/admin/modules');
    await page.getByTestId('enable-blog-site').selectOption({ label: `${siteKey} (Reauth E2E)` });
    await page.getByTestId('enable-blog').click();
    await expect(page.getByTestId('reauth-modal-backdrop')).toBeVisible();
    await page.getByTestId('reauth-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('reauth-confirm').click();
    await expect(page.getByTestId('reauth-modal-backdrop')).toBeHidden({ timeout: 15_000 });
  });

  test('wizard hints explain blocked install blog step', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('admin-login').click();
    await page.goto('/admin/modules');
    await expect(page.getByTestId('install-blog-step-hint')).toBeVisible();
    const hint = await page.getByTestId('install-blog-step-hint').innerText();
    expect(hint.length).toBeGreaterThan(10);
    const installed = await page.getByTestId('module-row-karkas-blog').getByText('Установлен').isVisible().catch(() => false);
    if (!installed) {
      expect(hint).toMatch(/Core|войдите|уже выполнен|установ/i);
    }
  });
});
