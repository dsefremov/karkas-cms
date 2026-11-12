import { expect, type Page } from '@playwright/test';
const ADMIN_PASSWORD = process.env.KARKAS_ADMIN_PASSWORD ?? 'test-admin-password-stage2';

const authHeaders = (): Record<string, string> => ({
  'Content-Type': 'application/json',
  'X-Karkas-Reauth': ADMIN_PASSWORD,
});

export async function seedE2ePageTree(page: Page): Promise<{ aboutSlug: string; teamSlug: string; siteId: number }> {
  const sitesRes = await page.request.get('/api/v1/sites');
  expect(sitesRes.ok(), await sitesRes.text()).toBeTruthy();
  const sitesJson = (await sitesRes.json()) as {
    sites?: Array<{ id: number; is_default?: boolean; site_key?: string }>;
  };
  const site =
    sitesJson.sites?.find((row) => row.is_default) ??
    sitesJson.sites?.[0];
  expect(site?.id).toBeTruthy();

  const suffix = Date.now().toString(36);
  const aboutSlug = `about-${suffix}`;
  const teamSlug = `team-${suffix}`;

  const aboutRes = await page.request.post(`/api/v1/sites/${site!.id}/pages`, {
    headers: authHeaders(),
    data: {
      title: 'О компании',
      slug: aboutSlug,
      activity: 'active',
      body_text: 'О нас',
    },
  });
  expect(aboutRes.ok(), await aboutRes.text()).toBeTruthy();
  const about = (await aboutRes.json()) as { id: string };

  const teamRes = await page.request.post(`/api/v1/sites/${site!.id}/pages`, {
    headers: authHeaders(),
    data: {
      title: 'Команда',
      slug: teamSlug,
      parent_id: about.id,
      activity: 'active',
      body_text: 'Команда',
    },
  });
  expect(teamRes.ok(), await teamRes.text()).toBeTruthy();

  return { aboutSlug, teamSlug, siteId: site!.id };
}

export async function ensurePageTreeSite(page: Page, siteId: number): Promise<void> {
  const siteSelect = page.getByTestId('page-tree-site');
  if ((await siteSelect.count()) === 0) {
    return;
  }
  await siteSelect.selectOption(String(siteId));
}
