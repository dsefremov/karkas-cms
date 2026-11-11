import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';

test.describe('Karkas Stage 6', () => {
  test('blog enable site selector lists sites', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'sites');
    const siteKey = `sel-${Date.now()}`;
    await page.getByTestId('new-site-key').fill(siteKey);
    await page.getByTestId('new-site-label').fill('Селектор E2E');
    await page.getByTestId('new-site-host').fill(`${siteKey}.example.test`);
    await page.getByTestId('create-site').click();
    await expect(page.getByTestId('admin-message')).toContainText('создан', { timeout: 30_000 });
    await gotoAdminPath(page, 'modules');
    const select = page.getByTestId('enable-blog-site');
    await expect(select).toBeVisible();
    await expect(select.getByRole('option', { name: `${siteKey} (Селектор E2E)` })).toHaveCount(1);
    await select.selectOption({ label: `${siteKey} (Селектор E2E)` });
    expect(Number(await select.inputValue())).toBeGreaterThan(0);
  });

  test('page tree seed and move preview', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    await page.getByTestId('page-tree-seed').click();
    await expect(page.getByTestId('admin-message')).toContainText('Демо-дерево', { timeout: 15_000 });
    await page.getByRole('button', { name: /Команда/ }).last().click();
    await page.getByTestId('move-parent').fill('00000000-0000-0000-0000-000000000001');
    await page.getByTestId('page-move-preview').click();
    await expect(page.getByTestId('page-move-impact')).toContainText('адресов', { timeout: 10_000 });
  });

  test('page tree screenshots at 1440 and 390', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    await page.getByTestId('page-tree-seed').click();
    await expect(page.getByTestId('admin-message')).toContainText('Демо-дерево', { timeout: 15_000 });
    await expect(page.getByRole('button', { name: /О компании/ }).last()).toBeVisible({ timeout: 15_000 });
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    const name = viewport === '1440' ? `tree-${viewport}` : `tree-${viewport}`;
    await page.screenshot({ path: testInfo.outputPath(`${name}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).include('[data-testid="page-tree-panel"]').analyze();
      expect(results.violations).toEqual([]);
    }
  });

  test('page move preview screenshot desktop', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Move dialog layout checked on desktop');
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    await page.getByTestId('page-tree-seed').click();
    await page.getByRole('button', { name: /Команда/ }).last().click();
    await page.getByTestId('page-move-preview').click();
    await expect(page.getByTestId('page-move-impact')).toContainText('адресов');
    await page.screenshot({ path: testInfo.outputPath('tree-move-1440.png'), fullPage: true });
  });

  test('admin screenshots at 1440 and 390 with site selector', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'modules');
    await expect(page.getByTestId('enable-blog-site')).toBeVisible();
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
