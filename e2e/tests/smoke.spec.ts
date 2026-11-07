import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe('Karkas Stage 0 smoke', () => {
  test('home API page responds', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    const body = await page.locator('body').innerText();
    expect(body).toMatch(/Karkas|status/);
  });

  test('admin placeholder renders with accessibility scan', async ({ page }, testInfo) => {
    await page.goto('/admin/');
    await expect(page.getByTestId('admin-shell')).toBeVisible();

    const screenshotName = `admin-${testInfo.project.name}.png`;
    await page.screenshot({ path: testInfo.outputPath(screenshotName), fullPage: true });

    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations).toEqual([]);
  });
});
