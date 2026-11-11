import { VersionsPanel } from '../components/VersionsPanel';
import { useAdmin } from '../context/AdminContext';

export function ModulesPage(): React.JSX.Element {
  const {
    authenticated,
    zipEnabled,
    sites,
    enableBlogSiteId,
    setEnableBlogSiteId,
    installerStepDisabled,
    installerStepHint,
    installBlogStepDisabled,
    installBlogStepHint,
    enableBlogStepDisabled,
    enableBlogStepHint,
    runInstaller,
    installBlog,
    scanIncoming,
    enableBlog,
  } = useAdmin();

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Модули</h1>
          <p>Установщик, каталог и включение blog на сайте.</p>
        </div>
      </div>
      <section className="card" data-testid="installer-panel">
        <h2>Установщик и модули</h2>
        <p className="step-hint" data-testid="installer-step-hint">
          {installerStepHint}
        </p>
        <p className="step-hint" data-testid="install-blog-step-hint">
          {installBlogStepHint}
        </p>
        <div className="actions">
          <button
            type="button"
            className="btn"
            data-testid="installer-run"
            onClick={() => void runInstaller()}
            disabled={installerStepDisabled}
            title={installerStepHint}
          >
            Завершить установку Core
          </button>
          <button
            type="button"
            className="btn"
            data-testid="install-blog"
            onClick={() => void installBlog()}
            disabled={installBlogStepDisabled}
            title={installBlogStepHint}
          >
            Установить blog из каталога
          </button>
          <button type="button" className="btn" data-testid="scan-incoming" onClick={() => void scanIncoming()} disabled={!authenticated}>
            Сканировать incoming
          </button>
        </div>
        <p className="muted installer-footnote">ZIP upload: {zipEnabled ? 'включён (файл-флаг на сервере)' : 'выключен по умолчанию'}</p>
      </section>

      <section className="card" data-testid="site-blog-panel">
        <h2>Blog на сайте</h2>
        <p className="step-hint" data-testid="enable-blog-step-hint">
          {enableBlogStepHint}
        </p>
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
          <button
            type="button"
            className="btn"
            data-testid="enable-blog"
            onClick={() => void enableBlog()}
            disabled={enableBlogStepDisabled}
            title={enableBlogStepHint}
          >
            Включить blog на выбранном сайте
          </button>
        </div>
      </section>

      <VersionsPanel />
    </>
  );
}
