import { expect, type Locator, type Page } from '@playwright/test';

/** Компонент AdminSelect (combobox + listbox): открыть список и вернуть его. */
export async function openAdminSelect(page: Page, testId: string): Promise<Locator> {
  const trigger = page.getByTestId(testId);
  await expect(trigger).toBeEnabled();
  if ((await trigger.getAttribute('aria-expanded')) !== 'true') {
    await trigger.click();
  }
  const listbox = page.getByTestId(`${testId}-listbox`);
  await expect(listbox).toBeVisible();
  return listbox;
}

/** Выбрать вариант по видимой подписи. */
export async function chooseAdminSelectLabel(page: Page, testId: string, label: string): Promise<void> {
  const listbox = await openAdminSelect(page, testId);
  await listbox.getByRole('option', { name: label, exact: true }).click();
  await expect(listbox).toBeHidden();
  await expect(page.getByTestId(testId)).toContainText(label);
}

/** Выбрать вариант по значению (data-value). */
export async function chooseAdminSelectValue(page: Page, testId: string, value: string): Promise<void> {
  const listbox = await openAdminSelect(page, testId);
  await listbox.locator(`[role="option"][data-value="${value}"]`).click();
  await expect(listbox).toBeHidden();
  await expect(page.getByTestId(testId)).toHaveAttribute('data-value', value);
}

export async function adminSelectValue(page: Page, testId: string): Promise<string> {
  return (await page.getByTestId(testId).getAttribute('data-value')) ?? '';
}

/** id сайта по ключу (для выбора сайта в AdminSelect по значению: названия сайтов могут совпадать). */
export async function siteIdByKey(page: Page, siteKey: string): Promise<string> {
  const response = await page.request.get('/api/v1/sites');
  expect(response.ok(), await response.text()).toBeTruthy();
  const body = (await response.json()) as { sites: Array<{ id: number; site_key: string }> };
  const site = body.sites.find((row) => row.site_key === siteKey);
  expect(site, `site ${siteKey}`).toBeDefined();
  return String(site!.id);
}
