/** Человекочитаемые названия пакетов каталога (resources/package-catalog). */
export function packageLabel(packageId: string): string {
  switch (packageId) {
    case 'karkas/core':
      return 'Ядро';
    case 'karkas/blog':
      return 'Блог';
    default:
      return packageId;
  }
}
