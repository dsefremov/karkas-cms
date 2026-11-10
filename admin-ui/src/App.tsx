import { useCallback, useEffect, useState } from 'react';

type PackageVersionRow = {
  package_id: string;
  current: string | null;
  available: string | null;
  status: string;
};

type VersionsPayload = {
  packages: PackageVersionRow[];
  stage: number;
};

type BlogVersionRow = {
  version_seq: number;
  saved_at: string;
  title: string;
  activity: string;
};

export function App(): React.JSX.Element {
  const [password, setPassword] = useState('');
  const [authenticated, setAuthenticated] = useState(false);
  const [versions, setVersions] = useState<VersionsPayload | null>(null);
  const [zipEnabled, setZipEnabled] = useState(false);
  const [maintenance, setMaintenance] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [coreUpgradeConfirm, setCoreUpgradeConfirm] = useState(false);
  const [lastPostId, setLastPostId] = useState<string | null>(null);
  const [blogHistory, setBlogHistory] = useState<BlogVersionRow[]>([]);

  const loadVersions = useCallback(async () => {
    const response = await fetch('/api/v1/platform/versions');
    if (response.ok) {
      setVersions((await response.json()) as VersionsPayload);
    }
  }, []);

  const refreshAdmin = useCallback(async () => {
    const [session, zip, maint] = await Promise.all([
      fetch('/api/v1/admin/session'),
      fetch('/api/v1/admin/packages/zip-upload'),
      fetch('/api/v1/admin/platform/maintenance'),
    ]);
    if (session.ok) {
      const body = (await session.json()) as { authenticated: boolean };
      setAuthenticated(body.authenticated);
    }
    if (zip.ok) {
      const body = (await zip.json()) as { enabled: boolean };
      setZipEnabled(body.enabled);
    }
    if (maint.ok) {
      const body = (await maint.json()) as { enabled: boolean };
      setMaintenance(body.enabled);
    }
    await loadVersions();
  }, [loadVersions]);

  useEffect(() => {
    void refreshAdmin();
  }, [refreshAdmin]);

  async function login(): Promise<void> {
    setError(null);
    const response = await fetch('/api/v1/admin/session', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password }),
    });
    if (!response.ok) {
      setError('Неверный пароль администратора');
      return;
    }
    setAuthenticated(true);
    await refreshAdmin();
  }

  async function runInstaller(): Promise<void> {
    setMessage(null);
    setError(null);
    setMessage('Установка Core…');
    await fetch('/api/v1/installer/bootstrap', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: '{}',
    });
    const complete = await fetch('/api/v1/installer/complete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: '{}',
    });
    if (!complete.ok) {
      setError('Ошибка завершения установщика');
      return;
    }
    setMessage('Установщик: Core зарегистрирован атомарно.');
    await loadVersions();
  }

  async function installBlog(): Promise<void> {
    setMessage(null);
    setError(null);
    setMessage('Установка blog…');
    const response = await fetch('/api/v1/platform/install', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: JSON.stringify({ modules: ['karkas/blog'] }),
    });
    if (!response.ok) {
      setError('Установка модуля отклонена');
      return;
    }
    setMessage('Модуль blog установлен (installed_disabled).');
    await loadVersions();
  }

  async function scanIncoming(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/admin/packages/scan-incoming', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: '{}',
    });
    if (!response.ok) {
      setError('Сканирование incoming не выполнено');
      return;
    }
    const body = (await response.json()) as { discovered: string[] };
    setMessage(`Incoming: найдено ${body.discovered.length} READY-папок.`);
  }

  async function enableBlog(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/sites/default/modules/karkas/blog/enable', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: JSON.stringify({ site_label: 'Каркас — демо' }),
    });
    if (!response.ok) {
      setError('Включение blog на сайте отклонено');
      return;
    }
    setMessage('Blog включён на сайте (миграции + mount /blog).');
  }

  async function saveDemoPost(): Promise<void> {
    setMessage(null);
    setError(null);
    const slug = `demo-post-${Date.now()}`;
    const create = await fetch('/api/v1/blog/posts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: 'Демо-статья',
        slug,
        body_text: 'Контент статьи этапа 3.',
        activity: 'inactive',
      }),
    });
    if (!create.ok) {
      setError('Не удалось создать черновик');
      return;
    }
    const created = (await create.json()) as { id: string };
    const save = await fetch(`/api/v1/blog/posts/${created.id}/save`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: 'Демо-статья',
        slug,
        body_text: 'Контент статьи этапа 3.',
        activity: 'active',
      }),
    });
    if (!save.ok) {
      setError('Save статьи отклонён');
      return;
    }
    setMessage('Статья сохранена (manual Save, activity=active).');
    setLastPostId(created.id);
    setBlogHistory([]);
  }

  async function upgradeCore(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/platform/install', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: JSON.stringify({ modules: [], include_core: true }),
    });
    if (!response.ok) {
      setError('Обновление Core отклонено');
      return;
    }
    setMessage('Core обновлён по плану (include_core).');
    setCoreUpgradeConfirm(false);
    await loadVersions();
  }

  async function loadBlogHistory(): Promise<void> {
    setMessage(null);
    setError(null);
    if (null === lastPostId) {
      setError('Сначала сохраните демо-статью');
      return;
    }
    const response = await fetch(`/api/v1/blog/posts/${lastPostId}/versions`);
    if (!response.ok) {
      setError('Не удалось загрузить историю версий');
      return;
    }
    const body = (await response.json()) as { versions: BlogVersionRow[] };
    setBlogHistory(body.versions);
    setMessage(`История версий: ${body.versions.length} записей (макс. 10).`);
  }

  const coreRow = versions?.packages.find((row) => row.package_id === 'karkas/core');
  const coreUpgradeAvailable =
    coreRow !== undefined && coreRow.current !== null && coreRow.available !== null && coreRow.current !== coreRow.available;

  return (
    <div className="shell" data-testid="admin-shell" role="document">
      <aside className="sidebar" aria-label="Навигация">
        <div className="brand">Каркас</div>
        <nav>
          <span className="nav-item active">Пакеты</span>
          <span className="nav-item muted">Установщик</span>
        </nav>
      </aside>
      <main className="content">
        <header className="topbar">
          <h1>Админка — этап 4</h1>
          <p className="subtitle">G4: обновление Core через план, история версий поста (последние 10), публичный slug.</p>
        </header>

        {maintenance && (
          <div className="banner-warn" data-testid="maintenance-banner" role="status">
            Режим обслуживания активен — записи API заморожены.
          </div>
        )}

        {error !== null && (
          <p className="status-banner" role="alert" data-testid="admin-error">
            {error}
          </p>
        )}
        {message !== null && (
          <p className="status-banner" role="status" data-testid="admin-message">
            {message}
          </p>
        )}

        <section className="card" aria-labelledby="auth-heading">
          <h2 id="auth-heading">Вход администратора</h2>
          {!authenticated ? (
            <div className="form-row">
              <label htmlFor="admin-password">Пароль</label>
              <input
                id="admin-password"
                data-testid="admin-password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
              <button type="button" data-testid="admin-login" onClick={() => void login()}>
                Войти
              </button>
            </div>
          ) : (
            <p data-testid="admin-authenticated">Сессия активна (повторная аутентификация для установки через заголовок).</p>
          )}
        </section>

        <section className="card" data-testid="versions-panel">
          <h2>Версии</h2>
          <div className="table-scroll">
            <table className="versions-table">
              <thead>
                <tr>
                  <th scope="col">Пакет</th>
                  <th scope="col">Текущая</th>
                  <th scope="col">Доступная (локально)</th>
                </tr>
              </thead>
              <tbody>
                {versions?.packages.map((row) => (
                  <tr key={row.package_id} data-testid={`version-row-${row.package_id.replace('/', '-')}`}>
                    <td>{row.package_id}</td>
                    <td>{row.current ?? '—'}</td>
                    <td>{row.available ?? '—'}</td>
                </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        <section className="card" data-testid="core-upgrade-panel">
          <h2>Обновление Core</h2>
          {coreRow !== undefined ? (
            <p className="muted" data-testid="core-version-summary">
              Текущая: <strong>{coreRow.current ?? '—'}</strong> → доступная (локально):{' '}
              <strong>{coreRow.available ?? '—'}</strong>
            </p>
          ) : (
            <p className="muted">Данные Core недоступны</p>
          )}
          {coreUpgradeAvailable ? (
            <>
              <div className="form-row">
                <label htmlFor="core-upgrade-confirm">
                  <input
                    id="core-upgrade-confirm"
                    data-testid="core-upgrade-confirm"
                    type="checkbox"
                    checked={coreUpgradeConfirm}
                    onChange={(e) => setCoreUpgradeConfirm(e.target.checked)}
                  />{' '}
                  Подтверждаю явное обновление Core до {coreRow?.available}
                </label>
              </div>
              <div className="actions actions-stack">
                <button
                  type="button"
                  data-testid="upgrade-core"
                  onClick={() => void upgradeCore()}
                  disabled={!authenticated || !coreUpgradeConfirm}
                >
                  Обновить Core (include_core)
                </button>
              </div>
            </>
          ) : (
            <p className="muted">Обновление не требуется (текущая совпадает с локально доступной).</p>
          )}
        </section>

        <section className="card" data-testid="installer-panel">
          <h2>Установщик и модули</h2>
          <div className="actions">
            <button type="button" data-testid="installer-run" onClick={() => void runInstaller()} disabled={!authenticated}>
              Завершить установку Core
            </button>
            <button type="button" data-testid="install-blog" onClick={() => void installBlog()} disabled={!authenticated}>
              Установить blog из каталога
            </button>
            <button type="button" data-testid="scan-incoming" onClick={() => void scanIncoming()} disabled={!authenticated}>
              Сканировать incoming
            </button>
          </div>
          <p className="muted installer-footnote">ZIP upload: {zipEnabled ? 'включён (файл-флаг на сервере)' : 'выключен по умолчанию'}</p>
        </section>

        <section className="card" data-testid="site-blog-panel">
          <h2>Blog на сайте</h2>
          <div className="actions actions-stack">
            <button type="button" data-testid="enable-blog" onClick={() => void enableBlog()} disabled={!authenticated}>
              Включить blog на сайте
            </button>
            <button type="button" data-testid="blog-save-demo" onClick={() => void saveDemoPost()} disabled={!authenticated}>
              Сохранить демо-статью
            </button>
            <button type="button" data-testid="blog-load-history" onClick={() => void loadBlogHistory()} disabled={!authenticated}>
              Показать историю версий
            </button>
          </div>
          {lastPostId !== null && (
            <p className="muted" data-testid="blog-last-post-id">
              Последний пост: {lastPostId}
            </p>
          )}
          {blogHistory.length > 0 && (
            <div className="table-scroll" data-testid="blog-history-panel">
              <table className="versions-table">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Заголовок</th>
                    <th scope="col">Activity</th>
                  </tr>
                </thead>
                <tbody>
                  {blogHistory.map((row) => (
                    <tr key={row.version_seq} data-testid={`blog-version-row-${row.version_seq}`}>
                      <td>{row.version_seq}</td>
                      <td>{row.title}</td>
                      <td>{row.activity}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      </main>
    </div>
  );
}
