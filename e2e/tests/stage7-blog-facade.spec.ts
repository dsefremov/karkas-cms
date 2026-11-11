import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 7 blog facade', () => {
  test('bind and unbind blog to page tree entry', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    const suffix = Date.now().toString(36);
    await page.getByTestId('page-tree-seed').click();
    await expect(page.getByTestId('admin-message')).toContainText('Демо-дерево', { timeout: 15_000 });
    const journalButton = page.getByRole('button', { name: /О компании/ }).last();
    await journalButton.click();
    await page.getByTestId('page-edit-slug').fill(`journal-${suffix}`);
    await page.getByTestId('page-save').click();
    await expect(page.getByTestId('admin-message')).toContainText('сохранена', { timeout: 15_000 });
    await page.getByTestId('blog-bind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('привязан', { timeout: 15_000 });
    await expect(page.getByTestId('blog-facade-status')).toContainText('/blog');

    const journalPath = `/journal-${suffix}`;
    const redirect = await page.request.get(journalPath);
    expect(redirect.status()).toBe(301);
    expect(redirect.headers()['location']).toBe('/blog');

    await page.getByTestId('blog-unbind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('отвязан', { timeout: 15_000 });
    const blogRedirect = await page.request.get('/blog');
    expect([301, 302]).toContain(blogRedirect.status());
    expect(blogRedirect.headers()['location']).toBe(journalPath);
  });
});
