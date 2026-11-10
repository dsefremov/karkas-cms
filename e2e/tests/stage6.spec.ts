import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 6', () => {
  test('blog enable site selector lists sites', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    const siteKey = `sel-${Date.now()}`;
    await page.getByTestId('new-site-key').fill(siteKey);
    await page.getByTestId('new-site-label').fill('Селектор E2E');
    await page.getByTestId('new-site-host').fill(`${siteKey}.example.test`);
    await page.getByTestId('create-site').click();
    await expect(page.getByTestId('admin-message')).toContainText('создан', { timeout: 30_000 });
    const select = page.getByTestId('enable-blog-site');
    await expect(select).toBeVisible();
    await expect(select.getByRole('option', { name: `${siteKey} (Селектор E2E)` })).toHaveCount(1);
    await select.selectOption({ label: `${siteKey} (Селектор E2E)` });
    expect(Number(await select.inputValue())).toBeGreaterThan(0);
  });

  test('admin screenshots at 1440 and 390 with site selector', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await expect(page.getByTestId('enable-blog-site')).toBeVisible();
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
