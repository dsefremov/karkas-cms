import { expect, type Page } from '@playwright/test';
import { ensureAdminLogin } from './admin-bootstrap';

export const ADMIN_ROUTES = [
  { testId: 'nav-dashboard', path: 'dashboard', marker: 'dashboard-panel' },
  { testId: 'nav-posts', path: 'posts', marker: 'blog-save-demo' },
  { testId: 'nav-categories', path: 'categories', marker: 'categories-panel' },
  { testId: 'nav-pages', path: 'pages', marker: 'page-tree-panel' },
  { testId: 'nav-users', path: 'users', marker: 'stage8-placeholder' },
  { testId: 'nav-modules', path: 'modules', marker: 'installer-panel' },
  { testId: 'nav-sites', path: 'sites', marker: 'sites-panel' },
  { testId: 'nav-settings', path: 'settings', marker: 'settings-panel' },
  { testId: 'nav-operations', path: 'operations', marker: 'operations-panel' },
  { testId: 'nav-roles', path: 'roles', marker: 'stage8-placeholder' },
] as const;

export async function gotoAdminPath(page: Page, path: string): Promise<void> {
  const normalized = path.replace(/^\//, '');
  await page.goto(`/admin/${normalized}`);
  await expect(page.getByTestId('admin-shell')).toBeVisible();
}

export async function openAdminAuthed(page: Page, path = 'dashboard'): Promise<void> {
  await ensureAdminLogin(page);
  await gotoAdminPath(page, path);
}
