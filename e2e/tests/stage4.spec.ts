import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';

test.describe('Karkas Stage 4', () => {
  test('core upgrade button updates Core version', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/core-update');
    const summary = (await page.getByTestId('core-version-summary').textContent()) ?? '';
    if (summary.includes('1.0.0') && summary.includes('1.1.0')) {
      await page.getByTestId('core-upgrade-confirm').check();
      await page.getByTestId('upgrade-core').click();
      await expect(page.getByTestId('admin-message')).toContainText('Core обновлён', { timeout: 60_000 });
      await expect(page.getByTestId('version-row-karkas-core')).toContainText('1.1.0');
    }
    await expect(page.getByTestId('core-upgrade-panel')).toContainText('не требуется');
  });

  test('blog version history shows at most ten rows', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/posts');
    await page.getByTestId('blog-save-demo').click();
    await expect(page.getByTestId('admin-message')).toContainText('Статья сохранена', { timeout: 60_000 });
    const postId = await page.getByTestId('blog-last-post-id').textContent();
    expect(postId).toContain('Последний пост:');
    const id = postId!.replace('Последний пост: ', '').trim();
    const slug = `hist-${Date.now()}`;

    for (let i = 1; i <= 11; ++i) {
      const save = await page.request.post(`/api/v1/blog/posts/${id}/save`, {
        headers: { 'Content-Type': 'application/json' },
        data: {
          title: `Версия ${i}`,
          slug,
          body_text: `Текст ${i}`,
          activity: 'active',
        },
      });
      expect(save.ok(), await save.text()).toBeTruthy();
    }

    await page.getByTestId('blog-load-history').click();
    await expect(page.getByTestId('admin-message')).toContainText('10 записей', { timeout: 60_000 });
    await expect(page.getByTestId('blog-history-panel').getByRole('row')).toHaveCount(11);
  });

  test('admin screenshots at 1440 and 390 with axe on desktop', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    await page.goto('/admin/posts');
    await page.getByTestId('blog-save-demo').click();
    await expect(page.getByTestId('admin-message')).toContainText('Статья сохранена', { timeout: 60_000 });
    await page.getByTestId('blog-load-history').click();
    await expect(page.getByTestId('blog-history-panel')).toBeVisible({ timeout: 30_000 });

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });

    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
