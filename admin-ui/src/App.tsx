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

type SiteRow = {
  id: number;
  site_key: string;
  label: string;
  is_default: boolean;
  canonical_host: string | null;
  blog_enabled: boolean;
};

const PAGE_TREE_ROOT = '00000000-0000-0000-0000-000000000001';

type PageTreeNode = {
  id: string;
  parent_id: string;
  slug: string;
  sort_order: number;
  title: string;
  activity: string;
  full_path: string;
  children: PageTreeNode[];
};

type CorePlanPayload = {
  compatible: boolean;
  errors: string[];
  plan: {
    core_version?: string;
    targets?: Array<{ package_id: string; version: string }>;
  };
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
  const [sites, setSites] = useState<SiteRow[]>([]);
  const [enableBlogSiteId, setEnableBlogSiteId] = useState(1);
  const [newSiteKey, setNewSiteKey] = useState('partner');
  const [newSiteLabel, setNewSiteLabel] = useState('Партнёрский сайт');
  const [newSiteHost, setNewSiteHost] = useState('partner.example.test');
  const [corePlan, setCorePlan] = useState<CorePlanPayload | null>(null);
  const [pageTreeSiteId, setPageTreeSiteId] = useState(1);
  const [pageTree, setPageTree] = useState<PageTreeNode[]>([]);
  const [selectedPageId, setSelectedPageId] = useState<string | null>(null);
  const [pageTitle, setPageTitle] = useState('');
  const [pageSlug, setPageSlug] = useState('');
  const [pageBody, setPageBody] = useState('');
  const [pageActivity, setPageActivity] = useState<'active' | 'inactive'>('inactive');
  const [moveParentId, setMoveParentId] = useState(PAGE_TREE_ROOT);
  const [moveImpact, setMoveImpact] = useState<number | null>(null);

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
    const sitesResponse = await fetch('/api/v1/sites');
    if (sitesResponse.ok) {
      const body = (await sitesResponse.json()) as { sites: SiteRow[] };
      setSites(body.sites);
      const defaultSite = body.sites.find((s) => s.is_default) ?? body.sites[0];
      if (defaultSite !== undefined) {
        setEnableBlogSiteId(defaultSite.id);
        setPageTreeSiteId(defaultSite.id);
      }
    }
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
    const response = await fetch(`/api/v1/sites/${enableBlogSiteId}/modules/karkas/blog/enable`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: JSON.stringify({ site_label: 'Каркас — демо' }),
    });
    if (!response.ok) {
      setError('Включение blog на сайте отклонено');
      return;
    }
    const site = sites.find((s) => s.id === enableBlogSiteId);
    setMessage(`Blog включён на сайте «${site?.site_key ?? enableBlogSiteId}» (миграции + mount /blog).`);
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

  async function loadCorePlan(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/platform/plan?include_core=1');
    if (!response.ok) {
      setError('Не удалось построить план обновления Core');
      setCorePlan(null);
      return;
    }
    setCorePlan((await response.json()) as CorePlanPayload);
    setMessage('План обновления Core загружен (include_core).');
  }

  async function loadSites(): Promise<void> {
    const response = await fetch('/api/v1/sites');
    if (response.ok) {
      const body = (await response.json()) as { sites: SiteRow[] };
      setSites(body.sites);
    }
  }

  async function createSite(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/sites', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: JSON.stringify({
        site_key: newSiteKey,
        label: newSiteLabel,
        canonical_host: newSiteHost || null,
      }),
    });
    if (!response.ok) {
      setError('Не удалось создать сайт');
      return;
    }
    setMessage(`Сайт «${newSiteLabel}» создан (multisite).`);
    await loadSites();
  }

  async function makeDefaultSite(siteId: number): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch(`/api/v1/sites/${siteId}/make-default`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
      body: '{}',
    });
    const body = (await response.json()) as { message?: string };
    if (!response.ok) {
      setError(body.message ?? 'Не удалось назначить сайт по умолчанию');
      return;
    }
    setMessage('Сайт по умолчанию обновлён.');
    await refreshAdmin();
  }

  async function deleteSite(siteId: number, siteKey: string): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch(`/api/v1/sites/${siteId}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-Karkas-Reauth': password },
    });
    const body = (await response.json()) as { message?: string };
    if (!response.ok) {
      setError(body.message ?? 'Не удалось удалить сайт');
      return;
    }
    setMessage(`Сайт «${siteKey}» удалён.`);
    await refreshAdmin();
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

  async function loadPageTree(): Promise<void> {
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/pages/tree`);
    if (!response.ok) {
      return;
    }
    const body = (await response.json()) as { tree: PageTreeNode[] };
    setPageTree(body.tree);
  }

  function flattenTree(nodes: PageTreeNode[], acc: PageTreeNode[] = []): PageTreeNode[] {
    for (const node of nodes) {
      acc.push(node);
      flattenTree(node.children, acc);
    }
    return acc;
  }

  function selectPageNode(node: PageTreeNode): void {
    setSelectedPageId(node.id);
    setPageTitle(node.title);
    setPageSlug(node.slug);
    setPageBody('');
    setPageActivity(node.activity === 'active' ? 'active' : 'inactive');
    setMoveParentId(node.parent_id);
    setMoveImpact(null);
  }

  async function seedDemoPages(): Promise<void> {
    setError(null);
    setMessage(null);
    const suffix = Date.now().toString(36);
    const aboutSlug = `about-${suffix}`;
    const teamSlug = `team-${suffix}`;
    const aboutRes = await fetch(`/api/v1/sites/${pageTreeSiteId}/pages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: 'О компании', slug: aboutSlug, activity: 'active', body_text: 'О нас' }),
    });
    if (!aboutRes.ok) {
      const err = (await aboutRes.json()) as { message?: string };
      setError(err.message ?? 'Не удалось создать корневую страницу');
      return;
    }
    const about = (await aboutRes.json()) as PageTreeNode;
    const teamRes = await fetch(`/api/v1/sites/${pageTreeSiteId}/pages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: 'Команда',
        slug: teamSlug,
        parent_id: about.id,
        activity: 'active',
        body_text: 'Команда',
      }),
    });
    if (!teamRes.ok) {
      setError('Не удалось создать дочернюю страницу');
      return;
    }
    setMessage(`Демо-дерево страниц создано (${aboutSlug}/${teamSlug}).`);
    await loadPageTree();
  }

  async function saveSelectedPage(): Promise<void> {
    if (selectedPageId === null) {
      setError('Выберите страницу в дереве');
      return;
    }
    const response = await fetch(`/api/v1/pages/${selectedPageId}/save`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: pageTitle,
        slug: pageSlug,
        body_text: pageBody,
        activity: pageActivity,
      }),
    });
    if (!response.ok) {
      setError('Сохранение страницы отклонено');
      return;
    }
    setMessage('Страница сохранена.');
    await loadPageTree();
  }

  async function previewPageMove(): Promise<void> {
    if (selectedPageId === null) {
      setError('Выберите страницу для переноса');
      return;
    }
    const response = await fetch(`/api/v1/pages/${selectedPageId}/move-preview`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ parent_id: moveParentId, slug: pageSlug }),
    });
    const body = (await response.json()) as { affected_paths?: number; message?: string };
    if (!response.ok) {
      setError(body.message ?? 'Перенос недоступен');
      setMoveImpact(null);
      return;
    }
    setMoveImpact(body.affected_paths ?? 0);
    setMessage(`Изменится адресов: ${body.affected_paths ?? 0}`);
  }

  async function confirmPageMove(): Promise<void> {
    if (selectedPageId === null) {
      return;
    }
    const response = await fetch(`/api/v1/pages/${selectedPageId}/move`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ parent_id: moveParentId, sort_order: 0, slug: pageSlug }),
    });
  if (!response.ok) {
      setError('Перенос отклонён');
      return;
    }
    setMessage('Страница перенесена.');
    setMoveImpact(null);
    await loadPageTree();
  }

  async function moveSelectedSibling(direction: -1 | 1): Promise<void> {
    if (selectedPageId === null) {
      return;
    }
    const flat = flattenTree(pageTree);
    const current = flat.find((n) => n.id === selectedPageId);
    if (current === undefined) {
      return;
    }
    const siblings = flat
      .filter((n) => n.parent_id === current.parent_id)
      .sort((a, b) => a.sort_order - b.sort_order);
    const index = siblings.findIndex((n) => n.id === selectedPageId);
    const target = siblings[index + direction];
    if (target === undefined) {
      return;
    }
    await fetch(`/api/v1/pages/${selectedPageId}/move`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        parent_id: current.parent_id,
        sort_order: target.sort_order,
        slug: current.slug,
      }),
    });
    await loadPageTree();
  }

  function renderTreeNodes(nodes: PageTreeNode[], depth = 0): React.ReactNode {
    return nodes.map((node) => (
      <li key={node.id} className="page-tree-item" style={{ marginLeft: `${depth * 1.25}rem` }}>
        <button
          type="button"
          className={selectedPageId === node.id ? 'page-tree-node selected' : 'page-tree-node'}
          data-testid={`page-tree-node-${node.slug}`}
          onClick={() => selectPageNode(node)}
        >
          {node.title} <span className="muted">/{node.full_path}</span>
        </button>
        {node.children.length > 0 && <ul className="page-tree-children">{renderTreeNodes(node.children, depth + 1)}</ul>}
      </li>
    ));
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
          <span className="nav-item muted" data-testid="nav-pages">
            Страницы
          </span>
          <span className="nav-item muted">Установщик</span>
        </nav>
      </aside>
      <main className="content">
        <header className="topbar">
          <h1>Админка — этап 7</h1>
          <p className="subtitle">G7: FULLTEXT-поиск, legacy /p/→полный путь, управление default site и блогом.</p>
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
          <div className="actions actions-stack">
            <button type="button" data-testid="load-core-plan" onClick={() => void loadCorePlan()} disabled={!authenticated}>
              Показать план обновления Core
            </button>
          </div>
          {corePlan !== null && (
            <pre className="plan-preview" data-testid="core-plan-preview">
              {JSON.stringify(corePlan.plan?.targets ?? [], null, 2)}
            </pre>
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

        <section className="card" data-testid="sites-panel">
          <h2>Сайты (multisite)</h2>
          <div className="table-scroll">
            <table className="versions-table">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Ключ</th>
                  <th scope="col">Название</th>
                  <th scope="col">Host</th>
                  <th scope="col">Default</th>
                  <th scope="col">Блог включён</th>
                  <th scope="col">Действия</th>
                </tr>
              </thead>
              <tbody>
                {sites.map((site) => (
                  <tr key={site.id} data-testid={`site-row-${site.site_key}`}>
                    <td>{site.id}</td>
                    <td>{site.site_key}</td>
                    <td>{site.label}</td>
                    <td>{site.canonical_host ?? '—'}</td>
                    <td data-testid={`site-default-${site.site_key}`}>{site.is_default ? 'да' : '—'}</td>
                    <td data-testid={`site-blog-${site.site_key}`}>{site.blog_enabled ? 'да' : '—'}</td>
                    <td className="site-actions">
                      {!site.is_default && (
                        <button
                          type="button"
                          data-testid={`make-default-${site.site_key}`}
                          onClick={() => void makeDefaultSite(site.id)}
                          disabled={!authenticated}
                        >
                          Сделать сайтом по умолчанию
                        </button>
                      )}
                      {!site.is_default && (
                        <button
                          type="button"
                          data-testid={`delete-site-${site.site_key}`}
                          onClick={() => void deleteSite(site.id, site.site_key)}
                          disabled={!authenticated}
                        >
                          Удалить
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="form-row">
            <label htmlFor="new-site-key">Ключ</label>
            <input id="new-site-key" data-testid="new-site-key" value={newSiteKey} onChange={(e) => setNewSiteKey(e.target.value)} />
            <label htmlFor="new-site-label">Название</label>
            <input id="new-site-label" data-testid="new-site-label" value={newSiteLabel} onChange={(e) => setNewSiteLabel(e.target.value)} />
            <label htmlFor="new-site-host">Canonical host</label>
            <input id="new-site-host" data-testid="new-site-host" value={newSiteHost} onChange={(e) => setNewSiteHost(e.target.value)} />
            <button type="button" data-testid="create-site" onClick={() => void createSite()} disabled={!authenticated}>
              Создать сайт
            </button>
          </div>
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

        <section className="card page-tree-card" data-testid="page-tree-panel">
          <h2>Дерево страниц</h2>
          <div className="form-row">
            <label htmlFor="page-tree-site">Сайт</label>
            <select
              id="page-tree-site"
              data-testid="page-tree-site"
              value={pageTreeSiteId}
              onChange={(e) => setPageTreeSiteId(Number(e.target.value))}
              disabled={!authenticated}
            >
              {sites.map((site) => (
                <option key={site.id} value={site.id}>
                  {site.site_key}
                </option>
              ))}
            </select>
            <button type="button" data-testid="page-tree-refresh" onClick={() => void loadPageTree()} disabled={!authenticated}>
              Обновить дерево
            </button>
            <button type="button" data-testid="page-tree-seed" onClick={() => void seedDemoPages()} disabled={!authenticated}>
              Демо-страницы
            </button>
          </div>
          <div className="page-tree-layout">
            <ul className="page-tree-list" data-testid="page-tree-list">
              {renderTreeNodes(pageTree)}
            </ul>
            <div className="page-tree-detail" data-testid="page-tree-detail">
              <h3>Перенос и редактирование</h3>
              <label htmlFor="page-edit-title">Заголовок</label>
              <input
                id="page-edit-title"
                data-testid="page-edit-title"
                value={pageTitle}
                onChange={(e) => setPageTitle(e.target.value)}
                disabled={selectedPageId === null}
              />
              <label htmlFor="page-edit-slug">Slug</label>
              <input
                id="page-edit-slug"
                data-testid="page-edit-slug"
                value={pageSlug}
                onChange={(e) => setPageSlug(e.target.value)}
                disabled={selectedPageId === null}
              />
              <label htmlFor="move-parent">Новый родитель (UUID)</label>
              <input
                id="move-parent"
                data-testid="move-parent"
                value={moveParentId}
                onChange={(e) => setMoveParentId(e.target.value)}
                disabled={selectedPageId === null}
              />
              <p className="muted">
                Корень: <code>{PAGE_TREE_ROOT}</code>
              </p>
              <p className="move-impact" data-testid="page-move-impact" aria-live="polite">
                {moveImpact !== null ? `Будет изменено адресов: ${moveImpact}` : 'Предпросмотр переноса покажет число затронутых URL.'}
              </p>
              <div className="actions actions-stack">
                <button type="button" data-testid="page-tree-key-up" onClick={() => void moveSelectedSibling(-1)} disabled={!authenticated}>
                  ↑ Выше (клавиатура)
                </button>
                <button type="button" data-testid="page-tree-key-down" onClick={() => void moveSelectedSibling(1)} disabled={!authenticated}>
                  ↓ Ниже (клавиатура)
                </button>
                <button type="button" data-testid="page-move-preview" onClick={() => void previewPageMove()} disabled={!authenticated}>
                  Предпросмотр переноса
                </button>
                <button type="button" data-testid="page-move-confirm" onClick={() => void confirmPageMove()} disabled={!authenticated}>
                  Подтвердить перенос
                </button>
                <button type="button" data-testid="page-save" onClick={() => void saveSelectedPage()} disabled={!authenticated}>
                  Сохранить страницу
                </button>
              </div>
            </div>
          </div>
        </section>

        <section className="card" data-testid="site-blog-panel">
          <h2>Blog на сайте</h2>
          <div className="form-row">
            <label htmlFor="enable-blog-site">Сайт для enable blog</label>
            <select
              id="enable-blog-site"
              data-testid="enable-blog-site"
              value={enableBlogSiteId}
              onChange={(e) => setEnableBlogSiteId(Number(e.target.value))}
              disabled={!authenticated || sites.length === 0}
            >
              {sites.map((site) => (
                <option key={site.id} value={site.id}>
                  {site.site_key} ({site.label}){site.is_default ? ' — default' : ''}
                </option>
              ))}
            </select>
          </div>
          <div className="actions actions-stack">
            <button type="button" data-testid="enable-blog" onClick={() => void enableBlog()} disabled={!authenticated}>
              Включить blog на выбранном сайте
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
