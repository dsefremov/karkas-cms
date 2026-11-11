import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 7 blog facade', () => {
  test('bind and unbind blog to page tree entry', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await page.getByTestId('page-tree-seed').click();
    await expect(page.getByTestId('admin-message')).toContainText('Демо-дерево', { timeout: 15_000 });
    await page.getByRole('button', { name: /О компании/ }).last().click();
    const slug = await page.getByTestId('page-edit-slug').inputValue();
    const treePath = `/${slug}`;
    await page.getByTestId('blog-bind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('привязан', { timeout: 15_000 });
    await expect(page.getByTestId('blog-facade-status')).toContainText('/blog');

    const redirect = await page.request.get(treePath);
    expect(redirect.status()).toBe(301);
    expect(redirect.headers()['location']).toBe('/blog');

    await page.getByTestId('blog-unbind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('отвязан', { timeout: 15_000 });
    const blogRedirect = await page.request.get('/blog');
    expect([301, 302]).toContain(blogRedirect.status());
    expect(blogRedirect.headers()['location']).toBe(treePath);
  });
});
