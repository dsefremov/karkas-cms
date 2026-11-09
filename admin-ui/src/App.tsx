import { useEffect, useState } from 'react';

type PackageVersionRow = {
  package_id: string;
  current: string | null;
  available: string | null;
  target: string | null;
  status: string;
  compatibility_reason: string | null;
};

type VersionsPayload = {
  schema: string;
  stage: number;
  sdk_version: string;
  packages: PackageVersionRow[];
};

export function App(): React.JSX.Element {
  const [versions, setVersions] = useState<VersionsPayload | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    void fetch('/api/v1/platform/versions')
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json() as Promise<VersionsPayload>;
      })
      .then((data) => {
        if (!cancelled) {
          setVersions(data);
        }
      })
      .catch((error: unknown) => {
        if (!cancelled) {
          setLoadError(error instanceof Error ? error.message : 'load failed');
        }
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="shell" data-testid="admin-shell" role="document">
      <aside className="sidebar" aria-label="Навигация">
        <div className="brand">Каркас</div>
        <nav>
          <span className="nav-item active">Обзор</span>
          <span className="nav-item muted">Контент</span>
          <span className="nav-item muted">Модули</span>
        </nav>
      </aside>
      <main className="content">
        <header className="topbar">
          <h1>Админка — этап 1</h1>
          <p className="subtitle">Контракты пакетов, SemVer и локальный каталог доступных версий.</p>
        </header>
        <section className="card" aria-labelledby="versions-heading" data-testid="versions-panel">
          <h2 id="versions-heading">Версии Core и модулей</h2>
          {loadError !== null && <p role="alert">Не удалось загрузить версии: {loadError}</p>}
          {versions === null && loadError === null && <p>Загрузка…</p>}
          {versions !== null && (
            <>
              <p className="subtitle">SDK {versions.sdk_version}; метаданные available не доверенные до плана установки.</p>
              <table className="versions-table">
                <thead>
                  <tr>
                    <th scope="col">Пакет</th>
                    <th scope="col">Текущая</th>
                    <th scope="col">Доступная</th>
                    <th scope="col">Статус</th>
                  </tr>
                </thead>
                <tbody>
                  {versions.packages.map((row) => (
                    <tr key={row.package_id} data-testid={`version-row-${row.package_id.replace('/', '-')}`}>
                      <td>{row.package_id}</td>
                      <td>{row.current ?? '—'}</td>
                      <td>{row.available ?? '—'}</td>
                      <td>{row.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </>
          )}
        </section>
      </main>
    </div>
  );
}
