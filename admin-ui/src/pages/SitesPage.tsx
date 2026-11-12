import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

export function SitesPage(): React.JSX.Element {
  const {
    authenticated,
    sites,
    newSiteKey,
    setNewSiteKey,
    newSiteLabel,
    setNewSiteLabel,
    newSiteHost,
    setNewSiteHost,
    createSite,
    makeDefaultSite,
    deleteSite,
  } = useAdmin();

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Сайты и шаблоны</h1>
          <p>Multisite, домены и правила выбора шаблона (вторая очередь — read-only).</p>
        </div>
      </div>
      <div className="tabs">
        <span className="tab active">Сайты</span>
        <span className="tab muted">Правила шаблонов</span>
      </div>
      <section className="table-card mb24" data-testid="sites-panel">
        <table className="data">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Ключ</th>
              <th scope="col">Название</th>
              <th scope="col">Host</th>
              <th scope="col">Default</th>
              <th scope="col">Блог</th>
              <th scope="col">Действия</th>
            </tr>
          </thead>
          <tbody>
            {sites.length === 0 ? (
              <tr>
                <td colSpan={7}>
                  <div className="empty-state">Сайтов пока нет.</div>
                </td>
              </tr>
            ) : (
              sites.map((site) => (
                <tr key={site.id} data-testid={`site-row-${site.site_key}`}>
                  <td>{site.id}</td>
                  <td>{site.site_key}</td>
                  <td>{site.label}</td>
                  <td>{site.canonical_host ?? '—'}</td>
                  <td data-testid={`site-default-${site.site_key}`}>{site.is_default ? 'да' : '—'}</td>
                  <td data-testid={`site-blog-${site.site_key}`}>{site.blog_enabled ? 'да' : '—'}</td>
                  <td>
                    {!site.is_default ? (
                      <MotionSiteActions
                        siteKey={site.site_key}
                        siteId={site.id}
                        authenticated={authenticated}
                        makeDefaultSite={makeDefaultSite}
                        deleteSite={deleteSite}
                      />
                    ) : (
                      '—'
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </section>
      <section className="card form-card mb24">
        <h2 className="mb16">Новый сайт</h2>
        <div className="grid2">
          <div className="field">
            <label htmlFor="new-site-key">
              Ключ
              <input
                id="new-site-key"
                data-testid="new-site-key"
                value={newSiteKey}
                onChange={(e) => setNewSiteKey(e.target.value)}
              />
            </label>
          </div>
          <div className="field">
            <label htmlFor="new-site-label">
              Название
              <input
                id="new-site-label"
                data-testid="new-site-label"
                value={newSiteLabel}
                onChange={(e) => setNewSiteLabel(e.target.value)}
              />
            </label>
          </div>
          <div className="field">
            <label htmlFor="new-site-host">
              Canonical host
              <input
                id="new-site-host"
                data-testid="new-site-host"
                value={newSiteHost}
                onChange={(e) => setNewSiteHost(e.target.value)}
              />
            </label>
          </div>
        </div>
        <button
          type="button"
          className="btn primary mt16"
          data-testid="create-site"
          onClick={() => void createSite()}
          disabled={!authenticated}
        >
          Создать сайт
        </button>
      </section>
      <section className="card">
        <h2 className="mb16">Правила шаблонов</h2>
        <p className="small muted mb16">
          Как в макете <code>site-templates.html</code> — API правил нет (этап 8). Показана структура без демо-данных.
        </p>
        <div className="notice warning">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>
            Multisite routing и приоритеты шаблонов отложены. См.{' '}
            <Link to="/pages">дерево страниц</Link> и{' '}
            <Link to="/modules/blog/binding">привязку blog</Link>.
          </div>
        </div>
      </section>
      <AdminFooter />
    </>
  );
}

function MotionSiteActions({
  siteKey,
  siteId,
  authenticated,
  makeDefaultSite,
  deleteSite,
}: {
  siteKey: string;
  siteId: number;
  authenticated: boolean;
  makeDefaultSite: (id: number) => Promise<void>;
  deleteSite: (id: number, key: string) => Promise<void>;
}): React.JSX.Element {
  return (
    <div className="row gap8 wrap">
      <button
        type="button"
        className="btn"
        data-testid={`make-default-${siteKey}`}
        onClick={() => void makeDefaultSite(siteId)}
        disabled={!authenticated}
      >
        Default
      </button>
      <button
        type="button"
        className="btn"
        data-testid={`delete-site-${siteKey}`}
        onClick={() => void deleteSite(siteId, siteKey)}
        disabled={!authenticated}
      >
        Удалить
      </button>
    </div>
  );
}
