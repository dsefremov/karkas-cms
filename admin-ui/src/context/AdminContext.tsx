import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { ReauthModal } from '../components/ReauthModal';
import { formatApiError } from '../lib/apiError';
import {
  clearStoredReauth,
  effectiveReauth,
  isReauthRequiredMessage,
  loadStoredReauth,
  reauthHeaders,
  storeReauth,
} from '../lib/reauth';
import {
  PAGE_TREE_ROOT,
  type BlogVersionRow,
  type CorePlanPayload,
  type PageTreeNode,
  type SiteRow,
  type VersionsPayload,
} from '../types/admin';

export type AdminContextValue = {
  password: string;
  setPassword: (value: string) => void;
  authenticated: boolean;
  sessionReady: boolean;
  versions: VersionsPayload | null;
  zipEnabled: boolean;
  maintenance: boolean;
  message: string | null;
  error: string | null;
  setMessage: (value: string | null) => void;
  setError: (value: string | null) => void;
  coreUpgradeConfirm: boolean;
  setCoreUpgradeConfirm: (value: boolean) => void;
  lastPostId: string | null;
  blogHistory: BlogVersionRow[];
  sites: SiteRow[];
  enableBlogSiteId: number;
  setEnableBlogSiteId: (id: number) => void;
  newSiteKey: string;
  setNewSiteKey: (value: string) => void;
  newSiteLabel: string;
  setNewSiteLabel: (value: string) => void;
  newSiteHost: string;
  setNewSiteHost: (value: string) => void;
  corePlan: CorePlanPayload | null;
  pageTreeSiteId: number;
  setPageTreeSiteId: (id: number) => void;
  pageTree: PageTreeNode[];
  selectedPageId: string | null;
  pageTitle: string;
  setPageTitle: (value: string) => void;
  pageSlug: string;
  setPageSlug: (value: string) => void;
  pageBody: string;
  setPageBody: (value: string) => void;
  pageActivity: 'active' | 'inactive';
  setPageActivity: (value: 'active' | 'inactive') => void;
  moveParentId: string;
  setMoveParentId: (value: string) => void;
  moveImpact: number | null;
  moveSortOrder: number;
  setMoveSortOrder: (value: number) => void;
  movePathPreview: { from: string; to: string } | null;
  blogFacadeBoundPageId: string | null;
  blogFacadeTreePath: string | null;
  coreRow: VersionsPayload['packages'][number] | undefined;
  blogRow: VersionsPayload['packages'][number] | undefined;
  coreInstalled: boolean;
  blogInstalled: boolean;
  blogEnabledOnTarget: boolean;
  coreUpgradeAvailable: boolean;
  installerStepDisabled: boolean;
  installerStepHint: string;
  installBlogStepDisabled: boolean;
  installBlogStepHint: string;
  enableBlogStepDisabled: boolean;
  enableBlogStepHint: string;
  login: () => Promise<void>;
  refreshAdmin: () => Promise<void>;
  runInstaller: () => Promise<void>;
  installBlog: () => Promise<void>;
  scanIncoming: () => Promise<void>;
  enableBlog: () => Promise<void>;
  saveDemoPost: () => Promise<void>;
  loadCorePlan: () => Promise<void>;
  createSite: () => Promise<void>;
  makeDefaultSite: (siteId: number) => Promise<void>;
  deleteSite: (siteId: number, siteKey: string) => Promise<void>;
  upgradeCore: () => Promise<void>;
  loadPageTree: () => Promise<void>;
  bindBlogToSelectedPage: () => Promise<void>;
  unbindBlogFacade: () => Promise<void>;
  seedDemoPages: () => Promise<void>;
  saveSelectedPage: () => Promise<void>;
  previewPageMove: () => Promise<void>;
  confirmPageMove: () => Promise<void>;
  selectPage: (node: PageTreeNode) => void;
  createChildPage: (title: string, slug: string, parentId: string) => Promise<void>;
  moveSelectedSibling: (direction: -1 | 1) => Promise<void>;
  loadBlogHistory: () => Promise<void>;
  renderTreeNodes: (nodes: PageTreeNode[], depth?: number) => React.ReactNode;
};

const AdminContext = createContext<AdminContextValue | null>(null);

export function useAdmin(): AdminContextValue {
  const ctx = useContext(AdminContext);
  if (ctx === null) {
    throw new Error('useAdmin must be used within AdminProvider');
  }
  return ctx;
}

export function AdminProvider({ children }: { children: ReactNode }): React.JSX.Element {
  const [password, setPassword] = useState('');
  const [authenticated, setAuthenticated] = useState(false);
  const [sessionReady, setSessionReady] = useState(false);
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
  const [moveSortOrder, setMoveSortOrder] = useState(0);
  const [movePathPreview, setMovePathPreview] = useState<{ from: string; to: string } | null>(null);
  const [blogFacadeBoundPageId, setBlogFacadeBoundPageId] = useState<string | null>(null);
  const [blogFacadeTreePath, setBlogFacadeTreePath] = useState<string | null>(null);
  const [reauthOpen, setReauthOpen] = useState(false);
  const [reauthDraft, setReauthDraft] = useState('');
  const [reauthError, setReauthError] = useState<string | null>(null);
  const [reauthOperation, setReauthOperation] = useState('');
  const reauthResumeRef = useRef<(() => Promise<void>) | null>(null);

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
    setSessionReady(true);
  }, [loadVersions]);

  useEffect(() => {
    void refreshAdmin();
  }, [refreshAdmin]);

  useEffect(() => {
    document.body.classList.add('admin');
    return () => {
      document.body.classList.remove('admin');
    };
  }, []);

  async function ensureReauth(operation: string, run: () => Promise<void>): Promise<void> {
    if (effectiveReauth(password) !== '') {
      await run();
      return;
    }
    setReauthOperation(operation);
    setReauthDraft(loadStoredReauth() || password);
    setReauthError(null);
    reauthResumeRef.current = run;
    setReauthOpen(true);
  }

  async function confirmReauth(): Promise<void> {
    setReauthError(null);
    const response = await fetch('/api/v1/admin/reauth', {
      method: 'POST',
      headers: reauthHeaders(reauthDraft),
      body: '{}',
    });
    if (!response.ok) {
      setReauthError(await formatApiError(response, 'Неверный пароль администратора'));
      return;
    }
    storeReauth(reauthDraft);
    setPassword(reauthDraft);
    setReauthOpen(false);
    const resume = reauthResumeRef.current;
    reauthResumeRef.current = null;
    if (resume !== null) {
      await resume();
    }
  }

  function cancelReauth(): void {
    setReauthOpen(false);
    reauthResumeRef.current = null;
    setReauthError(null);
  }

  async function handleApiFailure(response: Response, fallback: string): Promise<void> {
    const detail = await formatApiError(response, fallback);
    if (response.status === 401 && isReauthRequiredMessage(detail)) {
      clearStoredReauth();
      setError(`${detail}. Подтвердите пароль в диалоге и повторите.`);
      setReauthOperation(fallback);
      setReauthDraft('');
      setReauthOpen(true);
      return;
    }
    setError(detail);
  }

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
    storeReauth(password);
    setAuthenticated(true);
    await refreshAdmin();
  }

  async function runInstaller(): Promise<void> {
    await ensureReauth('завершение установки Core', async () => {
      setMessage(null);
      setError(null);
      setMessage('Установка Core…');
      const secret = effectiveReauth(password);
      const bootstrap = await fetch('/api/v1/installer/bootstrap', {
        method: 'POST',
        headers: reauthHeaders(secret),
        body: '{}',
      });
      if (!bootstrap.ok) {
        await handleApiFailure(bootstrap, 'Ошибка bootstrap установщика');
        return;
      }
      const complete = await fetch('/api/v1/installer/complete', {
        method: 'POST',
        headers: reauthHeaders(secret),
        body: '{}',
      });
      if (!complete.ok) {
        await handleApiFailure(complete, 'Ошибка завершения установщика');
        return;
      }
      setMessage('Установщик: Core зарегистрирован атомарно.');
      await loadVersions();
    });
  }

  async function installBlog(): Promise<void> {
    await ensureReauth('установка модуля blog из каталога', async () => {
      setMessage(null);
      setError(null);
      setMessage('Установка blog…');
      const secret = effectiveReauth(password);
      const response = await fetch('/api/v1/platform/install', {
        method: 'POST',
        headers: reauthHeaders(secret),
        body: JSON.stringify({ modules: ['karkas/blog'] }),
      });
      if (!response.ok) {
        await handleApiFailure(response, 'Установка модуля отклонена');
        return;
      }
      setMessage('Модуль blog установлен (installed_disabled).');
      await loadVersions();
      await refreshAdmin();
    });
  }

  async function scanIncoming(): Promise<void> {
    setMessage(null);
    setError(null);
    const response = await fetch('/api/v1/admin/packages/scan-incoming', {
      method: 'POST',
      headers: reauthHeaders(effectiveReauth(password)),
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
    const site = sites.find((s) => s.id === enableBlogSiteId);
    const siteLabel = site?.label?.trim() || `Сайт ${enableBlogSiteId}`;
    await ensureReauth(`включение blog на сайте «${site?.site_key ?? enableBlogSiteId}»`, async () => {
      setMessage(null);
      setError(null);
      const secret = effectiveReauth(password);
      const response = await fetch(`/api/v1/sites/${enableBlogSiteId}/modules/karkas/blog/enable`, {
        method: 'POST',
        headers: reauthHeaders(secret),
        body: JSON.stringify({ site_label: siteLabel }),
      });
      if (!response.ok) {
        await handleApiFailure(response, 'Включение blog на сайте отклонено');
        return;
      }
      setMessage(`Blog включён на сайте «${site?.site_key ?? enableBlogSiteId}» (миграции + mount /blog).`);
      await refreshAdmin();
    });
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
      headers: reauthHeaders(effectiveReauth(password)),
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
      headers: reauthHeaders(effectiveReauth(password)),
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
      headers: reauthHeaders(effectiveReauth(password)),
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
      headers: reauthHeaders(effectiveReauth(password)),
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

  async function loadBlogFacade(): Promise<void> {
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/modules/karkas/blog/facade`);
    if (!response.ok) {
      setBlogFacadeBoundPageId(null);
      setBlogFacadeTreePath(null);
      return;
    }
    const body = (await response.json()) as {
      facade?: { bound_page_id?: string | null; facade_tree_path?: string | null };
    };
    setBlogFacadeBoundPageId(body.facade?.bound_page_id ?? null);
    setBlogFacadeTreePath(body.facade?.facade_tree_path ?? null);
  }

  async function loadPageTree(): Promise<void> {
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/pages/tree`);
    if (!response.ok) {
      return;
    }
    const body = (await response.json()) as { tree: PageTreeNode[] };
    setPageTree(body.tree);
    await loadBlogFacade();
  }

  async function bindBlogToSelectedPage(): Promise<void> {
    if (selectedPageId === null) {
      setError('Выберите страницу для привязки блога');
      return;
    }
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/modules/karkas/blog/bind-page`, {
      method: 'POST',
      headers: reauthHeaders(effectiveReauth(password)),
      body: JSON.stringify({ page_id: selectedPageId }),
    });
    const body = (await response.json()) as { message?: string };
    if (!response.ok) {
      setError(body.message ?? 'Не удалось привязать блог');
      return;
    }
    setMessage('Блог привязан: публичный вход /blog, путь дерева → редирект.');
    await loadBlogFacade();
  }

  async function unbindBlogFacade(): Promise<void> {
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/modules/karkas/blog/unbind-page`, {
      method: 'POST',
      headers: reauthHeaders(effectiveReauth(password)),
      body: JSON.stringify({}),
    });
    const body = (await response.json()) as { message?: string };
    if (!response.ok) {
      setError(body.message ?? 'Блог не был привязан к странице');
      return;
    }
    setMessage('Блог отвязан: /blog временно ведёт на последний путь дерева.');
    await loadBlogFacade();
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
    setMovePathPreview(null);
  }

  async function createChildPage(title: string, slug: string, parentId: string): Promise<void> {
    setError(null);
    const response = await fetch(`/api/v1/sites/${pageTreeSiteId}/pages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title, slug, parent_id: parentId, activity: 'active', body_text: title }),
    });
    if (!response.ok) {
      const err = (await response.json()) as { message?: string };
      setError(err.message ?? 'Не удалось создать страницу');
      return;
    }
    setMessage(`Страница «${title}» создана.`);
    await loadPageTree();
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
    const body = (await response.json()) as {
      affected_paths?: number;
      message?: string;
      path_changes?: Array<{ from: string; to: string; page_id?: string }>;
      blog_facade?: { bound_page_id?: string | null; tree_path?: string | null; note?: string };
    };
    if (!response.ok) {
      setError(body.message ?? 'Перенос недоступен');
      setMoveImpact(null);
      setMovePathPreview(null);
      return;
    }
    setMoveImpact(body.affected_paths ?? 0);
    const change =
      body.path_changes?.find((c) => c.page_id === selectedPageId) ?? body.path_changes?.[0];
    if (change !== undefined) {
      setMovePathPreview({ from: `/${change.from}`, to: `/${change.to}` });
    } else {
      setMovePathPreview(null);
    }
    const facadeNote =
      body.blog_facade?.note ??
      (body.blog_facade?.bound_page_id
        ? `Фасад блога: /blog, путь дерева ${body.blog_facade.tree_path ?? '—'}.`
        : '');
    setMessage(
      `Изменится адресов: ${body.affected_paths ?? 0}${facadeNote ? `. ${facadeNote}` : ''}`,
    );
  }

  async function confirmPageMove(): Promise<void> {
    if (selectedPageId === null) {
      return;
    }
    const response = await fetch(`/api/v1/pages/${selectedPageId}/move`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ parent_id: moveParentId, sort_order: moveSortOrder, slug: pageSlug }),
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
        {node.children.length > 0 && (
          <ul className="page-tree-children">{renderTreeNodes(node.children, depth + 1)}</ul>
        )}
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
  const blogRow = versions?.packages.find((row) => row.package_id === 'karkas/blog');
  const coreInstalled = Boolean(coreRow?.current);
  const blogInstalled = Boolean(blogRow?.current);
  const enableTargetSite = sites.find((site) => site.id === enableBlogSiteId);
  const blogEnabledOnTarget = enableTargetSite?.blog_enabled ?? false;
  const coreUpgradeAvailable =
    coreRow !== undefined && coreRow.current !== null && coreRow.available !== null && coreRow.current !== coreRow.available;

  const installerStepDisabled = !authenticated || coreInstalled;
  const installerStepHint = !authenticated
    ? 'Шаг 1: войдите в админку.'
    : coreInstalled
      ? 'Шаг 1 (Core): уже выполнен — можно переходить к установке blog.'
      : 'Шаг 1: завершите установку Core (bootstrap + complete).';

  const installBlogStepDisabled = !authenticated || !coreInstalled || blogInstalled;
  const installBlogStepHint = !authenticated
    ? 'Шаг 2: войдите в админку.'
    : !coreInstalled
      ? 'Шаг 2: сначала завершите установку Core.'
      : blogInstalled
        ? 'Шаг 2 (install blog): уже выполнен — можно включать на сайте.'
        : 'Шаг 2: установите модуль blog из каталога (installed_disabled).';

  const enableBlogStepDisabled = !authenticated || !blogInstalled || blogEnabledOnTarget;
  const enableBlogStepHint = !authenticated
    ? 'Шаг 3: войдите в админку.'
    : !blogInstalled
      ? 'Шаг 3: сначала установите blog из каталога.'
      : blogEnabledOnTarget
        ? 'Шаг 3: blog уже включён на выбранном сайте.'
        : 'Шаг 3: включите blog на выбранном сайте (миграции + mount /blog).';

  const value = useMemo<AdminContextValue>(
    () => ({
      password,
      setPassword,
      authenticated,
      sessionReady,
      versions,
      zipEnabled,
      maintenance,
      message,
      error,
      setMessage,
      setError,
      coreUpgradeConfirm,
      setCoreUpgradeConfirm,
      lastPostId,
      blogHistory,
      sites,
      enableBlogSiteId,
      setEnableBlogSiteId,
      newSiteKey,
      setNewSiteKey,
      newSiteLabel,
      setNewSiteLabel,
      newSiteHost,
      setNewSiteHost,
      corePlan,
      pageTreeSiteId,
      setPageTreeSiteId,
      pageTree,
      selectedPageId,
      pageTitle,
      setPageTitle,
      pageSlug,
      setPageSlug,
      pageBody,
      setPageBody,
      pageActivity,
      setPageActivity,
      moveParentId,
      setMoveParentId,
      moveImpact,
      moveSortOrder,
      setMoveSortOrder,
      movePathPreview,
      blogFacadeBoundPageId,
      blogFacadeTreePath,
      coreRow,
      blogRow,
      coreInstalled,
      blogInstalled,
      blogEnabledOnTarget,
      coreUpgradeAvailable,
      installerStepDisabled,
      installerStepHint,
      installBlogStepDisabled,
      installBlogStepHint,
      enableBlogStepDisabled,
      enableBlogStepHint,
      login,
      refreshAdmin,
      runInstaller,
      installBlog,
      scanIncoming,
      enableBlog,
      saveDemoPost,
      loadCorePlan,
      createSite,
      makeDefaultSite,
      deleteSite,
      upgradeCore,
      loadPageTree,
      bindBlogToSelectedPage,
      unbindBlogFacade,
      seedDemoPages,
      saveSelectedPage,
      previewPageMove,
      confirmPageMove,
      selectPage: selectPageNode,
      createChildPage,
      moveSelectedSibling,
      loadBlogHistory,
      renderTreeNodes,
    }),
    [
      password,
      authenticated,
      sessionReady,
      versions,
      zipEnabled,
      maintenance,
      message,
      error,
      coreUpgradeConfirm,
      lastPostId,
      blogHistory,
      sites,
      enableBlogSiteId,
      newSiteKey,
      newSiteLabel,
      newSiteHost,
      corePlan,
      pageTreeSiteId,
      pageTree,
      selectedPageId,
      pageTitle,
      pageSlug,
      pageBody,
      pageActivity,
      moveParentId,
      moveImpact,
      moveSortOrder,
      movePathPreview,
      blogFacadeBoundPageId,
      blogFacadeTreePath,
      coreRow,
      blogRow,
      coreInstalled,
      blogInstalled,
      blogEnabledOnTarget,
      coreUpgradeAvailable,
      installerStepDisabled,
      installerStepHint,
      installBlogStepDisabled,
      installBlogStepHint,
      enableBlogStepDisabled,
      enableBlogStepHint,
    ],
  );

  return (
    <AdminContext.Provider value={value}>
      {children}
      <ReauthModal
        open={reauthOpen}
        operation={reauthOperation}
        password={reauthDraft}
        error={reauthError}
        onPasswordChange={setReauthDraft}
        onCancel={cancelReauth}
        onConfirm={() => void confirmReauth()}
      />
    </AdminContext.Provider>
  );
}
