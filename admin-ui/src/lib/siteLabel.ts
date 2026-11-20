import type { SiteRow } from '../types/admin';

export function siteDisplayLabel(site: SiteRow): string {
  const base = site.label.trim() !== '' ? site.label.trim() : site.site_key;
  return site.is_default ? `${base} — default` : base;
}