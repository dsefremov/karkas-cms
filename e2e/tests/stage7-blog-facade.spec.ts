import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';
import { ensurePageTreeSite, seedE2ePageTree } from './helpers/page-tree-seed';

const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

test.describe('Karkas Stage 7 blog facade', () => {
  test('bind and unbind blog to page tree entry', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Bind/unbind flow verified on desktop');
    await bootstrapBlogAdmin(page);
    await gotoAdminPath(page, 'pages');
    await expect(page.getByTestId('page-tree-site')).toBeVisible();
    const siteId = await page.getByTestId('page-tree-site').inputValue();
    expect(Number(siteId)).toBeGreaterThan(0);
    const facadeProbe = await page.request.get(`/api/v1/sites/${siteId}/modules/karkas/blog/facade`);
    expect(facadeProbe.ok(), await facadeProbe.text()).toBeTruthy();

    const { aboutSlug, siteId: seedSiteId } = await seedE2ePageTree(page);
    const treePath = `/${aboutSlug}`;

    await gotoAdminPath(page, 'pages');
    await ensurePageTreeSite(page, seedSiteId);
    const aboutNode = page.getByTestId(`page-tree-node-${aboutSlug}`);
    await expect(aboutNode).toBeVisible({ timeout: 15_000 });
    await aboutNode.click();

    await page.goto('/admin/modules/blog/binding');
    await expect(page.getByTestId('module-blog-binding-panel')).toBeVisible();
    const bindTreeRow = page.getByTestId(`page-tree-node-${aboutSlug}`);
    await expect(bindTreeRow).toBeVisible({ timeout: 15_000 });
    await bindTreeRow.click();
    await page.evaluate(
      (pwd) => sessionStorage.setItem('karkas.admin.reauth', pwd),
      ADMIN_PASSWORD,
    );
    await page.getByTestId('blog-bind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('привязан', { timeout: 15_000 });

    await expect.poll(async () => (await page.request.get(treePath, { maxRedirects: 0 })).status()).toBe(301);
    const redirect = await page.request.get(treePath, { maxRedirects: 0 });
    expect(redirect.headers()['location']).toBe('/blog');

    await gotoAdminPath(page, 'modules/blog/unbind-ready');
    await page.evaluate(
      (pwd) => sessionStorage.setItem('karkas.admin.reauth', pwd),
      ADMIN_PASSWORD,
    );
    await page.getByTestId('blog-unbind-page').click();
    await expect(page.getByTestId('admin-message')).toContainText('отвязан', { timeout: 15_000 });
    const blogRedirect = await page.request.get('/blog', { maxRedirects: 0 });
    expect(blogRedirect.status()).toBe(301);
    expect(blogRedirect.headers()['location']).toBe(treePath);
  });
});
