import { test, expect, type Page } from '@playwright/test';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { ADMIN_ROUTES, gotoAdminPath } from './helpers/admin-nav';

const EXTRA_PATHS = [
  { path: 'core-update', marker: 'core-upgrade-panel' },
  { path: 'posts/new', marker: 'post-editor-panel' },
] as const;

async function horizontalOverflow(page: Page): Promise<{ scrollWidth: number; viewport: number; offenders: string[] }> {
  return page.evaluate(() => {
    const viewport = document.documentElement.clientWidth;
    const offenders: string[] = [];
    for (const el of Array.from(document.querySelectorAll<HTMLElement>('main *'))) {
      const box = el.getBoundingClientRect();
      if (box.width > 0 && box.right > viewport + 1) {
        // Элементы внутри прокручиваемого контейнера (карточка таблицы) не создают прокрутку страницы.
        let scroller: HTMLElement | null = el.parentElement;
        let insideScroller = false;
        while (scroller !== null && scroller.tagName !== 'MAIN') {
          const style = getComputedStyle(scroller);
          if (style.overflowX === 'auto' || style.overflowX === 'scroll') {
            insideScroller = true;
            break;
          }
          scroller = scroller.parentElement;
        }
        if (!insideScroller) {
          offenders.push(`${el.tagName.toLowerCase()}.${el.className}`.slice(0, 80));
        }
      }
    }
    return { scrollWidth: document.documentElement.scrollWidth, viewport, offenders: offenders.slice(0, 5) };
  });
}

test.describe('Stage 7.5 layout: no horizontal page overflow', () => {
  test.beforeEach(async ({ page }) => {
    await bootstrapBlogAdmin(page);
  });

  for (const route of [...ADMIN_ROUTES, ...EXTRA_PATHS]) {
    test(`/admin/${route.path} fits the viewport`, async ({ page }) => {
      await gotoAdminPath(page, route.path);
      await expect(page.getByTestId(route.marker)).toBeVisible();
      const result = await horizontalOverflow(page);
      expect(result.offenders, JSON.stringify(result)).toEqual([]);
      expect(result.scrollWidth, JSON.stringify(result)).toBeLessThanOrEqual(result.viewport);
    });
  }
});
