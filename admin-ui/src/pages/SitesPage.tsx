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
          <p>Multisite: ключи, хосты и сайт по умолчанию.</p>
        </div>
      </div>
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
                        className="btn"
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
                        className="btn"
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
          <button type="button" className="btn primary" data-testid="create-site" onClick={() => void createSite()} disabled={!authenticated}>
            Создать сайт
          </button>
        </div>
      </section>
    </>
  );
}
