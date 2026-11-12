import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';
import { ensurePageTreeSite, seedE2ePageTree } from './helpers/page-tree-seed';

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

  test('page tree seed and move preview', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Move form verified on desktop');
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    const { teamSlug, siteId } = await seedE2ePageTree(page);
    await gotoAdminPath(page, 'pages');
    await ensurePageTreeSite(page, siteId);
    const teamNode = page.getByTestId(`page-tree-node-${teamSlug}`);
    await expect(teamNode).toBeVisible({ timeout: 15_000 });
    await teamNode.scrollIntoViewIfNeeded();
    await teamNode.click();
    await expect(page.getByRole('button', { name: /Переместить через форму/ })).toBeEnabled({ timeout: 10_000 });
    await page.getByRole('button', { name: /Переместить через форму/ }).click();
    await expect(page.getByTestId('page-move-modal')).toBeVisible();
    await page.getByTestId('move-parent-select').selectOption({ label: 'Корень сайта' });
    await page.getByTestId('page-move-preview').click();
    await expect(page.getByTestId('page-move-impact')).toContainText('Затронуто', { timeout: 10_000 });
  });

  test('page tree screenshots at 1440 and 390', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    const { aboutSlug, siteId } = await seedE2ePageTree(page);
    await gotoAdminPath(page, 'pages');
    await ensurePageTreeSite(page, siteId);
    await expect(page.getByTestId(`page-tree-node-${aboutSlug}`)).toBeVisible({ timeout: 15_000 });
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
    const { teamSlug, siteId } = await seedE2ePageTree(page);
    await gotoAdminPath(page, 'pages');
    await ensurePageTreeSite(page, siteId);
    const teamRow = page.getByTestId(`page-tree-node-${teamSlug}`);
    await expect(teamRow).toBeVisible({ timeout: 15_000 });
    await teamRow.click();
    await page.getByRole('button', { name: /Переместить через форму/ }).click();
    await expect(page.getByTestId('page-move-modal')).toBeVisible();
    await page.getByTestId('move-parent-select').selectOption({ label: 'Корень сайта' });
    await page.getByTestId('page-move-preview').click();
    await expect(page.getByTestId('page-move-impact')).toContainText('Затронуто');
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
