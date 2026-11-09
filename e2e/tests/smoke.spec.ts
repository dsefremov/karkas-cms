import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe('Karkas Stage 1 smoke', () => {
  test('home API page responds', async ({ page }, testInfo) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    const body = await page.locator('body').innerText();
    expect(body).toMatch(/Karkas|status/);

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({
      path: testInfo.outputPath(`home-${viewport}.png`),
      fullPage: true,
    });
  });

  test('admin versions panel renders with accessibility scan', async ({ page }, testInfo) => {
    await page.goto('/admin/');
    await expect(page.getByTestId('admin-shell')).toBeVisible();
    await expect(page.getByTestId('versions-panel')).toBeVisible();
    await expect(page.getByTestId('version-row-karkas-core')).toBeVisible();

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });

    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations).toEqual([]);
  });
});
