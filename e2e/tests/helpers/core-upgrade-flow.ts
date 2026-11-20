import { expect, type Locator, type Page } from '@playwright/test';

export async function waitForCoreUpdateScreen(page: Page): Promise<void> {
  await expect(page.getByTestId('core-upgrade-panel')).toBeVisible();
  await expect(page.getByTestId('core-version-summary')).not.toHaveText('—');
  const action: Locator = page
    .getByTestId('upgrade-core')
    .or(page.getByTestId('core-upgrade-idle'));
  await expect(action).toBeVisible({ timeout: 30_000 });
}

export async function runCoreUpgradeIfOffered(page: Page): Promise<void> {
  await waitForCoreUpdateScreen(page);
  const upgrade = page.getByTestId('upgrade-core');
  if ((await upgrade.count()) === 0) {
    return;
  }
  if (!(await upgrade.isVisible())) {
    return;
  }
  await expect(page.getByTestId('core-upgrade-confirm')).toBeVisible();
  const target = (await page.getByTestId('core-version-available').textContent())?.trim() ?? '';
  await page.getByTestId('core-upgrade-confirm').check();
  await upgrade.click();
  await expect(page.getByTestId('admin-message')).toContainText('Core обновлён', { timeout: 60_000 });
  if (target !== '') {
    await expect(page.getByTestId('core-version-summary')).toContainText(target);
    await expect(page.getByTestId('version-row-karkas-core').locator('td').nth(1)).toContainText(target);
  }
}

export async function expectCoreUpgradeIdle(page: Page): Promise<void> {
  await expect(page.getByTestId('core-upgrade-idle')).toBeVisible();
  await expect(page.getByTestId('core-upgrade-idle')).toContainText('не требуется');
}