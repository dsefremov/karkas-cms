import type { Page } from '@playwright/test';

export async function seedAdminDemo(page: Page): Promise<void> {
  const response = await page.request.post('/api/v1/admin/demo/seed');
  if (!response.ok()) {
    const body = await response.text();
    throw new Error(`demo seed failed: ${response.status()} ${body}`);
  }
}
