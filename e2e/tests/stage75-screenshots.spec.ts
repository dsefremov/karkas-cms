import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import { ensureAdminLogin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SHOTS = path.resolve(__dirname, '../../docs/stage-7.5/screenshots');

const CAPTURES = [
  { slug: 'login', path: 'login', public: true },
  { slug: 'dashboard', path: 'dashboard' },
  { slug: 'posts', path: 'posts' },
  { slug: 'categories', path: 'categories' },
  { slug: 'pages', path: 'pages' },
  { slug: 'modules', path: 'modules' },
  { slug: 'sites', path: 'sites' },
  { slug: 'settings', path: 'settings' },
  { slug: 'operations', path: 'operations' },
  { slug: 'core-update', path: 'core-update' },
  { slug: 'users', path: 'users' },
  { slug: 'roles', path: 'roles' },
] as const;

test.describe('Stage 7.5 screenshot capture for docs', () => {
  for (const screen of CAPTURES) {
    test(`capture ${screen.slug}`, async ({ page }, testInfo) => {
      const vp = testInfo.project.name.includes('mobile') ? '390' : '1440';
      if (screen.public === true) {
        await page.goto('/admin/login');
      } else {
        await ensureAdminLogin(page);
        await gotoAdminPath(page, screen.path);
      }
      await expect(page.getByTestId('admin-shell')).toBeVisible();
      await page.screenshot({
        path: path.join(SHOTS, `result-${vp}-${screen.slug}.png`),
        fullPage: true,
      });
    });
  }
});

test.describe('Stage 7.5 mockup baselines present', () => {
  test('mock png files exist for key screens', () => {
    const required = ['mock-1440-dashboard.png', 'mock-1440-modules.png', 'mock-1440-login.png'];
    for (const name of required) {
      expect(fs.existsSync(path.join(SHOTS, name))).toBeTruthy();
    }
  });
});
