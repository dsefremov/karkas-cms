import { expect, type Page } from '@playwright/test';

export async function createAndPublishPostViaEditor(
  page: Page,
  options: { title: string; slug: string; body: string },
): Promise<string> {
  await page.goto('/admin/posts/new');
  await expect(page.getByTestId('post-editor-panel')).toBeVisible({ timeout: 30_000 });
  await expect(page.getByTestId('post-editor-save')).toBeEnabled({ timeout: 30_000 });
  await page.getByTestId('post-editor-title').fill(options.title);
  await page.getByTestId('post-editor-slug').fill(options.slug);
  await page.getByTestId('post-editor-body').fill(options.body);
  await page.getByTestId('post-editor-activity').selectOption('active');
  const createResponse = page.waitForResponse(
    (response) => response.url().includes('/api/v1/blog/posts') && response.request().method() === 'POST',
  );
  await page.getByTestId('post-editor-save').click();
  const response = await createResponse;
  expect(response.ok(), await response.text()).toBeTruthy();
  await expect(page).toHaveURL(/\/admin\/posts\/[0-9a-f-]+$/i, { timeout: 15_000 });
  const match = page.url().match(/\/posts\/([0-9a-f-]+)$/i);
  expect(match).not.toBeNull();
  const postId = match![1];
  await page.getByTestId('post-editor-save').click();
  await expect(page.getByTestId('admin-message')).toContainText(/сохранен/i, { timeout: 30_000 });
  return postId;
}
