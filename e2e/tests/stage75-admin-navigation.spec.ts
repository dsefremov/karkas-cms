import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin, ensureAdminLogin } from './helpers/admin-bootstrap';
import { ADMIN_ROUTES, gotoAdminPath } from './helpers/admin-nav';

const DIRECT_PATHS = [
  { path: 'core-update', marker: 'core-upgrade-panel' },
  ...ADMIN_ROUTES,
] as const;

test.describe('Stage 7.5 admin navigation', () => {
  test('login screen direct load and reload', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/admin/login');
    await expect(page.getByTestId('admin-password')).toBeVisible();
    await page.reload();
    await expect(page.getByTestId('admin-password')).toBeVisible();
  });

  test.beforeEach(async ({ page }) => {
    await bootstrapBlogAdmin(page);
  });

  test('sidebar opens each screen', async ({ page }) => {
    await gotoAdminPath(page, 'dashboard');
    for (const route of ADMIN_ROUTES) {
      await page.getByTestId(route.testId).click();
      await expect(page).toHaveURL(new RegExp(`/admin/${route.path}`));
      await expect(page.getByTestId(route.marker)).toBeVisible();
    }
  });

  for (const route of DIRECT_PATHS) {
    test(`direct load and reload: /admin/${route.path}`, async ({ page }) => {
      await gotoAdminPath(page, route.path);
      await expect(page.getByTestId(route.marker)).toBeVisible();
      await page.reload();
      await expect(page.getByTestId(route.marker)).toBeVisible();
    });
  }
});
