import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { bootstrapBlogAdmin } from './helpers/admin-bootstrap';
import { createAndPublishPostViaEditor } from './helpers/blog-editor';

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
    await expect(page.getByTestId('core-upgrade-idle')).toContainText('не требуется');
  });

  test('blog version history shows at most ten rows', async ({ page }) => {
    await bootstrapBlogAdmin(page);
    const slug = `hist-${Date.now()}`;
    const id = await createAndPublishPostViaEditor(page, {
      title: 'Демо-статья',
      slug,
      body: 'Контент stage4.',
    });
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

    await page.goto(`/admin/posts/${id}/history`);
    await expect(page.getByTestId('post-history-panel')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('post-history-panel').getByRole('row')).toHaveCount(11);
  });

  test('admin screenshots at 1440 and 390 with axe on desktop', async ({ page }, testInfo) => {
    await bootstrapBlogAdmin(page);
    const postId = await createAndPublishPostViaEditor(page, {
      title: 'Axe демо',
      slug: `axe-${Date.now()}`,
      body: 'Текст',
    });
    await page.goto(`/admin/posts/${postId}/history`);
    await expect(page.getByTestId('post-history-panel')).toBeVisible({ timeout: 30_000 });

    const viewport = testInfo.project.name.includes('mobile') ? '390' : '1440';
    await page.screenshot({ path: testInfo.outputPath(`admin-${viewport}.png`), fullPage: true });

    if (!testInfo.project.name.includes('mobile')) {
      const results = await new AxeBuilder({ page }).analyze();
      expect(results.violations).toEqual([]);
    }
  });
});
