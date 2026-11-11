import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';

test.describe('Karkas Stage 7', () => {
  test('public search page with axe', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/search?q=демо');
    await expect(page.getByTestId('search-page')).toBeVisible();
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`search-${viewport}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).include('[data-testid="search-main"]').analyze();
      expect(results.violations).toEqual([]);
    }
  });

  test('admin screenshots 1440 and 390', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'sites');
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).include('[data-testid="sites-panel"]').analyze();
      expect(results.violations).toEqual([]);
    }
  });

  test('sites list shows blog enabled column and make default', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'sites');
    const siteKey = `s7-${Date.now()}`;
    await page.getByTestId('new-site-key').fill(siteKey);
    await page.getByTestId('new-site-label').fill('Stage7 site');
    await page.getByTestId('new-site-host').fill(`${siteKey}.example.test`);
    await page.getByTestId('create-site').click();
    await expect(page.getByTestId('admin-message')).toContainText('создан', { timeout: 30_000 });
    await expect(page.getByTestId(`site-blog-default`)).toContainText('да');
    await page.getByTestId(`make-default-${siteKey}`).click();
    await expect(page.getByTestId('admin-message')).toContainText('по умолчанию', { timeout: 15_000 });
    await expect(page.getByTestId(`site-default-${siteKey}`)).toContainText('да');
  });

});
