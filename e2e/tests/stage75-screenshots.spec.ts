import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import { bootstrapBlogAdmin, ensureAdminLogin } from './helpers/admin-bootstrap';
import { gotoAdminPath } from './helpers/admin-nav';
import { resetAdminDemoForScreenshots } from './helpers/demo-seed';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SHOTS = path.resolve(__dirname, '../../docs/stage-7.5/screenshots');

const CAPTURES = [
  { slug: 'login', path: 'login', public: true },
  { slug: 'dashboard', path: 'dashboard' },
  { slug: 'posts', path: 'posts' },
  { slug: 'post-editor', path: 'posts/new' },
  { slug: 'post-history', path: 'posts', historyFromApi: true },
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

type Capture = (typeof CAPTURES)[number] & {
  historyFromApi?: boolean;
};

/**
 * Снимки для docs/stage-7.5 снимаются только отдельным прогоном на свежей демо-базе
 * (scripts/capture-stage75-screenshots.sh: новый том MySQL → demo/reset → этот spec).
 * В обычном e2e-прогоне (CI, e2e-БД) захват пропускается: reset удалил бы e2e-сайты
 * и перезаписал бы снимки в docs данными тестов.
 */
const CAPTURE_ENABLED = process.env.KARKAS_SCREENSHOTS === '1';

test.describe('Stage 7.5 screenshot capture for docs', () => {
  test.skip(!CAPTURE_ENABLED, 'docs screenshots: run scripts/capture-stage75-screenshots.sh on a fresh demo DB');

  // Один reset+seed на проект (1440 / 390), а не перед каждым снимком.
  test.beforeAll(async ({ browser }, testInfo) => {
    const page = await browser.newPage({ baseURL: testInfo.project.use.baseURL });
    await bootstrapBlogAdmin(page);
    await resetAdminDemoForScreenshots(page);
    await page.close();
  });

  for (const screen of CAPTURES as readonly Capture[]) {
    test(`capture ${screen.slug}`, async ({ page }, testInfo) => {
      const vp = testInfo.project.name.includes('mobile') ? '390' : '1440';
      if (screen.public === true) {
        await page.goto('/admin/login');
      } else {
        await ensureAdminLogin(page);
        if (screen.historyFromApi === true) {
          const list = await page.request.get('/api/v1/blog/posts');
          const body = (await list.json()) as { posts?: Array<{ id: string }> };
          const first = body.posts?.[0];
          if (first !== undefined) {
            await gotoAdminPath(page, `posts/${first.id}/history`);
          } else {
            await gotoAdminPath(page, 'posts');
          }
        } else {
          await gotoAdminPath(page, screen.path);
        }
      }
      await expect(page.getByTestId('admin-shell')).toBeVisible();
      // Дождаться данных экрана: все запросы API завершены и нет индикатора загрузки.
      await page.waitForLoadState('networkidle');
      await expect(page.getByText('Загрузка…')).toHaveCount(0);
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
