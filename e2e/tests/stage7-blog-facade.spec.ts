import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 7 blog facade', () => {
  test('bind and unbind blog to page tree entry', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await expect(page.getByTestId('page-tree-site')).toBeVisible();
    const siteId = await page.getByTestId('page-tree-site').inputValue();
    expect(Number(siteId)).toBeGreaterThan(0);
    const facadeProbe = await page.request.get(`/api/v1/sites/${siteId}/modules/karkas/blog/facade`);
    expect(facadeProbe.ok(), await facadeProbe.text()).toBeTruthy();

    await page.getByTestId('page-tree-seed').click();
    await expect(page.getByTestId('admin-message')).toContainText('Демо-дерево', { timeout: 15_000 });
    await page.getByRole('button', { name: /О компании/ }).last().click();
    const slug = await page.getByTestId('page-edit-slug').inputValue();
    expect(slug.length).toBeGreaterThan(0);
    const treePath = `/${slug}`;
    await page.getByTestId('blog-bind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('привязан', { timeout: 15_000 });

    await expect
      .poll(async () => (await page.request.get(treePath, { maxRedirects: 0 })).status())
      .toBe(301);
    const redirect = await page.request.get(treePath, { maxRedirects: 0 });
    expect(redirect.headers()['location']).toBe('/blog');

    await page.getByTestId('blog-unbind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('отвязан', { timeout: 15_000 });
    const blogRedirect = await page.request.get('/blog', { maxRedirects: 0 });
    expect(blogRedirect.status()).toBe(301);
    expect(blogRedirect.headers()['location']).toBe(treePath);
  });
});
