import { expect, type Page } from '@playwright/test';
import { ensureAdminLogin } from './admin-bootstrap';

export const ADMIN_ROUTES = [
  { testId: 'nav-dashboard', path: 'dashboard', marker: 'dashboard-panel' },
  { testId: 'nav-posts', path: 'posts', marker: 'posts-list-panel' },
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

/** Клик по пункту сайдбара. На узкой ширине сайдбар свёрнут: сначала открыть его кнопкой «Меню». */
export async function clickSidebarNav(page: Page, testId: string): Promise<void> {
  const toggle = page.getByTestId('admin-menu-toggle');
  if (await toggle.isVisible()) {
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByTestId('admin-sidebar')).toHaveClass(/sidebar-mobile-open/);
  }
  await page.getByTestId(testId).click();
  if (await toggle.isVisible()) {
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  }
}
