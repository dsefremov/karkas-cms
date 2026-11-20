import type { SiteRow } from '../types/admin';

/**
 * Подпись сайта для выпадающих списков: название (не id/ключ).
 * Если название совпадает у нескольких сайтов, добавляется домен (или ключ), чтобы варианты различались.
 */
export function siteDisplayLabel(site: SiteRow, allSites: SiteRow[] = []): string {
  const name = siteName(site);
  const duplicate = allSites.some((other) => other.id !== site.id && siteName(other) === name);
  const base = duplicate ? `${name} · ${site.canonical_host ?? site.site_key}` : name;
  return site.is_default ? `${base} (по умолчанию)` : base;
}

function siteName(site: SiteRow): string {
  return site.label.trim() !== '' ? site.label.trim() : site.site_key;
}
