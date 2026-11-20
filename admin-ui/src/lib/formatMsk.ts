export function formatMskDateTime(raw: string): string {
  if (raw === '' || raw === '—') {
    return '—';
  }
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const withZone = /Z|[+-]\d{2}:\d{2}$/.test(normalized) ? normalized : `${normalized}Z`;
  const date = new Date(withZone);
  if (Number.isNaN(date.getTime())) {
    return raw;
  }
  return new Intl.DateTimeFormat('ru-RU', {
    timeZone: 'Europe/Moscow',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}
/** Дата и время по МСК раздельно, как в ячейках таблиц макета: «3 нояб. 2026 г.» / «14:30 МСК». */
export function formatMskParts(raw: string): { date: string; time: string } | null {
  if (raw === '' || raw === '—') {
    return null;
  }
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const withZone = /Z|[+-]\d{2}:\d{2}$/.test(normalized) ? normalized : `${normalized}Z`;
  const date = new Date(withZone);
  if (Number.isNaN(date.getTime())) {
    return null;
  }
  const day = new Intl.DateTimeFormat('ru-RU', {
    timeZone: 'Europe/Moscow',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
    .format(date)
    .replace(/\s?г\.$/, '');
  const time = new Intl.DateTimeFormat('ru-RU', {
    timeZone: 'Europe/Moscow',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
  return { date: day, time: `${time} МСК` };
}

/** Сортируемый ключ (UTC ISO) для строк дат API «YYYY-MM-DD HH:MM:SS.ffffff». */
export function utcSortKey(raw: string): string {
  return raw.includes('T') ? raw : raw.replace(' ', 'T');
}
