import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { waitForCoreUpdateScreen } from './helpers/core-upgrade-flow';

test.describe('Karkas Stage 5', () => {
  test('core plan preview shows targets before upgrade', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/core-update');
    const { current, available } = await waitForCoreUpdateScreen(page);
    const preview = page.getByTestId('core-plan-preview');
    await expect(preview).toBeVisible();
    await expect(page.getByTestId('core-plan-target')).toHaveText(available);
    if (current !== available) {
      await expect(page.getByTestId('plan-step-core')).toContainText(`karkas/core ${current} → ${available}`);
    } else {
      await expect(page.getByTestId('core-upgrade-idle')).toBeVisible();
    }
    // karkas/core показан один раз: в шаге плана, а не дублем в списке модулей.
    await expect(preview.getByText('karkas/core', { exact: false })).toHaveCount(current !== available ? 1 : 0);
  });

  test('multisite panel lists default site and can create partner', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/sites');
    await expect(page.getByTestId('sites-panel')).toBeVisible();
    const siteKey = `p-${Date.now()}`;
    await page.getByTestId('new-site-key').fill(siteKey);
    await page.getByTestId('new-site-label').fill('Партнёр E2E');
    await page.getByTestId('new-site-host').fill(`partner-${Date.now()}.example.test`);
    await page.getByTestId('create-site').click();
    await expect(page.getByTestId('admin-message')).toContainText('создан', { timeout: 30_000 });
    await expect(page.getByTestId(`site-row-${siteKey}`)).toBeVisible();
    const rowCount = await page.getByTestId('sites-panel').getByRole('row').count();
    expect(rowCount).toBeGreaterThanOrEqual(2);
  });

  test('admin screenshots at 1440 and 390 with plan panel', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/core-update');
    await expect(page.getByTestId('core-plan-preview')).toBeVisible({ timeout: 30_000 });
    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });
    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
