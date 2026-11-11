import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { createAndPublishPostViaEditor } from './helpers/blog-editor';

test.describe('Karkas Stage 6 smoke', () => {
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
    await createAndPublishPostViaEditor(page, {
      title: 'Демо-статья',
      slug: `demo-smoke-${Date.now()}`,
      body: 'Контент smoke e2e.',
    });
    await page.goto('/admin/modules');
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
