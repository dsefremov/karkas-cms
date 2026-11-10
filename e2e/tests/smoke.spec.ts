import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 5 smoke', () => {
  test('home API page responds', async ({ page }, testInfo) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({
      path: testInfo.outputPath(`home-${viewport}.png`),
      fullPage: true,
    });
  });

  test('admin blog save flow with accessibility scan', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await page.getByTestId('blog-save-demo').click();
    await expect(page.getByTestId('admin-message')).toContainText('Статья сохранена', { timeout: 60_000 });
    await expect(page.getByTestId('version-row-karkas-blog')).toContainText('1.0.0');

    const blog = await page.goto('/blog');
    expect(blog?.ok()).toBeTruthy();
    await expect(page.getByRole('listitem').filter({ hasText: 'Демо-статья' }).first()).toBeVisible();

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.goto('/admin/');
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });

    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
