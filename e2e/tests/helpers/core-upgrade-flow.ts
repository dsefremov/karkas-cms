import { expect, type Page } from '@playwright/test';

/** Версии из resources/package-catalog/karkas/core: чистая установка = 1.0.0, в каталоге доступна 1.1.0. */
export const CORE_INITIAL = '1.0.0';
export const CORE_AVAILABLE = '1.1.0';

/** Дождаться данных экрана: версии загружены и показан либо блок подтверждения, либо «не требуется». */
export async function waitForCoreUpdateScreen(page: Page): Promise<{ current: string; available: string }> {
  await expect(page.getByTestId('core-upgrade-panel')).toBeVisible();
  await expect(page.getByTestId('core-version-summary')).toHaveText(/^\d+\.\d+\.\d+$/, { timeout: 30_000 });
  await expect(page.getByTestId('core-version-available')).toHaveText(/^\d+\.\d+\.\d+$/);
  await expect(page.getByTestId('upgrade-core').or(page.getByTestId('core-upgrade-idle'))).toBeVisible();
  const current = (await page.getByTestId('core-version-summary').textContent())?.trim() ?? '';
  const available = (await page.getByTestId('core-version-available').textContent())?.trim() ?? '';
  return { current, available };
}

/**
 * Полный сценарий обновления Core.
 * На чистой БД (Core 1.0.0, доступна 1.1.0) обновление обязано быть предложено и выполнено.
 * Если Core уже обновлён ранее в этом прогоне (вторая проекция Playwright на той же БД) —
 * текущая обязана совпадать с доступной, и экран показывает «не требуется».
 */
export async function runCoreUpgradeFlow(page: Page): Promise<'upgraded' | 'already-current'> {
  const { current, available } = await waitForCoreUpdateScreen(page);
  if (current === CORE_INITIAL) {
    expect(available).toBe(CORE_AVAILABLE);
    const upgrade = page.getByTestId('upgrade-core');
    const confirm = page.getByTestId('core-upgrade-confirm');
    await expect(upgrade).toBeVisible();
    await expect(upgrade).toBeDisabled();
    await expect(page.getByTestId(`plan-step-core`)).toContainText(CORE_AVAILABLE);
    await confirm.check();
    await expect(upgrade).toBeEnabled();
    await upgrade.click();
    await expect(page.getByTestId('admin-message')).toContainText(`Core обновлён до ${CORE_AVAILABLE}`, {
      timeout: 60_000,
    });
    await expect(page.getByTestId('core-version-summary')).toHaveText(CORE_AVAILABLE);
    await expect(page.getByTestId('core-version-available')).toHaveText(CORE_AVAILABLE);
    await expect(page.getByTestId('core-plan-empty')).toContainText(CORE_AVAILABLE);
    await expectCoreUpgradeIdle(page);
    return 'upgraded';
  }
  expect(current).toBe(CORE_AVAILABLE);
  expect(available).toBe(CORE_AVAILABLE);
  await expectCoreUpgradeIdle(page);
  return 'already-current';
}

export async function expectCoreUpgradeIdle(page: Page): Promise<void> {
  await expect(page.getByTestId('core-upgrade-idle')).toBeVisible();
  await expect(page.getByTestId('core-upgrade-idle')).toContainText('не требуется');
  await expect(page.getByTestId('upgrade-core')).toHaveCount(0);
}
