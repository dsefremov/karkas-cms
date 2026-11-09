import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

test.describe('Karkas Stage 3 smoke', () => {
  test('home API page responds', async ({ page }, testInfo) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({
      path: testInfo.outputPath(`home-${viewport}.png`),
      fullPage: true,
    });
  });

  test('admin blog enable flow with accessibility scan', async ({ page }, testInfo) => {
    await page.goto('/admin/');
    await expect(page.getByTestId('admin-shell')).toBeVisible();
    await page.getByTestId('admin-password').fill(ADMIN_PASSWORD);
    await page.getByTestId('admin-login').click();
    await expect(page.getByTestId('admin-authenticated')).toBeVisible();
    await page.getByTestId('installer-run').click();
    await page.getByTestId('install-blog').click();
    await page.getByTestId('enable-blog').click();
    await expect(page.getByTestId('admin-message')).toContainText('Blog включён', { timeout: 30_000 });
    await page.getByTestId('blog-save-demo').click();
    await expect(page.getByTestId('admin-message')).toContainText('Статья сохранена', { timeout: 30_000 });
    await expect(page.getByTestId('version-row-karkas-blog')).toContainText('1.0.0');

    const blog = await page.goto('/blog');
    expect(blog?.ok()).toBeTruthy();
    await expect(page.getByRole('listitem').filter({ hasText: 'Демо-статья' }).first()).toBeVisible();

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.goto('/admin/');
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });

    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations).toEqual([]);
  });
});
