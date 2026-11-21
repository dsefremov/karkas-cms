import { expect, type Page } from '@playwright/test';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

const authHeaders = (): Record<string, string> => ({
  'Content-Type': 'application/json',
  'X-Karkas-Reauth': ADMIN_PASSWORD,
});

/** Для скриншотов: удалить e2e-сайты и заново наполнить демо-данными. Не вызывать в полном e2e-прогоне. */
export async function resetAdminDemoForScreenshots(page: Page): Promise<void> {
  const reset = await page.request.post('/api/v1/admin/demo/reset', {
    headers: authHeaders(),
    data: '{}',
  });
  expect(reset.ok(), await reset.text()).toBeTruthy();
}

export async function seedAdminDemo(page: Page): Promise<void> {
  if (process.env.KARKAS_DEMO_RESET === '1') {
    await resetAdminDemoForScreenshots(page);
    return;
  }
  const response = await page.request.post('/api/v1/admin/demo/seed', {
    headers: authHeaders(),
    data: '{}',
  });
  if (!response.ok()) {
    const body = await response.text();
    throw new Error(`demo seed failed: ${response.status()} ${body}`);
  }
}