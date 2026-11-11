import { Link } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

export function ModulesPage(): React.JSX.Element {
  const {
    authenticated,
    versions,
    zipEnabled,
    sites,
    enableBlogSiteId,
    setEnableBlogSiteId,
    coreInstalled,
    blogInstalled,
    blogEnabledOnTarget,
    coreRow,
    blogRow,
    coreUpgradeAvailable,
    runInstaller,
    installBlog,
    scanIncoming,
    enableBlog,
    installBlogStepHint,
    installerStepHint,
    enableBlogStepHint,
  } = useAdmin();

  const coreCurrent = coreRow?.current ?? null;
  const coreAvailable = coreRow?.available ?? null;
  const blogCurrent = blogRow?.current ?? null;
  const blogAvailable = blogRow?.available ?? null;

  return (
    <>
      <span data-testid="installer-step-hint" hidden aria-hidden="true">
        {installerStepHint}
      </span>
      <div className="page-head">
        <div>
          <h1>Модули</h1>
          <p>{coreCurrent ? `Ядро Каркас ${coreCurrent}` : 'Установка и пакеты платформы'}</p>
        </div>
        <div className="row gap8">
          <Link className="btn" to="/core-update">
            Обновление ядра
          </Link>
          <Link className="btn" to="/settings">
            {zipEnabled ? 'ZIP включён' : 'ZIP выключен · инструкция'}
          </Link>
        </div>
      </div>

      <div className="notice">
        <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
        <div>
          ZIP из админки или папка incoming: проверка manifest и совместимых версий. Устанавливайте только из
          проверенных источников.
        </div>
      </div>

      <div className="table-card mt24" data-testid="installer-panel">
        <table className="data">
          <thead>
            <tr>
              <th scope="col">Модуль / ID</th>
              <th scope="col">Текущая версия</th>
              <th scope="col">Доступная версия</th>
              <th scope="col">Состояние / проверка</th>
              <th scope="col">Действие</th>
            </tr>
          </thead>
          <tbody>
            <tr data-testid="module-row-karkas-core">
              <td>
                Каркас Core
                <div className="small muted">karkas/core</div>
              </td>
              <td>{coreCurrent ?? 'Не установлена'}</td>
              <td>
                {coreAvailable ?? '—'}
                {coreUpgradeAvailable && coreAvailable !== null ? (
                  <span className="badge green"> Обновление</span>
                ) : null}
              </td>
              <td>
                {coreInstalled ? (
                  <>
                    Установлен
                    <div className="small muted">{installerStepHint}</div>
                  </>
                ) : (
                  <>
                    Требуется bootstrap
                    <div className="small muted">{installerStepHint}</div>
                  </>
                )}
              </td>
              <td>
                {coreInstalled ? (
                  <span className="badge green">Установлен</span>
                ) : (
                  <button
                    type="button"
                    className="btn primary"
                    data-testid="installer-run"
                    onClick={() => void runInstaller()}
                    disabled={!authenticated}
                  >
                    Завершить установку Core
                  </button>
                )}
              </td>
            </tr>
            <tr data-testid="module-row-karkas-blog">
              <td>
                Блог
                <div className="small muted">karkas/blog</div>
              </td>
              <td>{blogCurrent ?? 'Не установлена'}</td>
              <td>
                {blogAvailable ?? '—'}
                {blogAvailable !== null && blogCurrent !== blogAvailable ? (
                  <span className="badge green"> Обновление</span>
                ) : null}
              </td>
              <td>
                {blogInstalled ? (
                  <>
                    Пакет в каталоге
                    <div className="small muted" data-testid="install-blog-step-hint">
                      {installBlogStepHint}
                    </div>
                  </>
                ) : (
                  <>
                    Не установлен
                    <div className="small muted" data-testid="install-blog-step-hint">
                      {installBlogStepHint}
                    </div>
                  </>
                )}
              </td>
              <td>
                {blogInstalled ? (
                  <span className="badge green">Установлен</span>
                ) : (
                  <button
                    type="button"
                    className="btn primary"
                    data-testid="install-blog"
                    onClick={() => void installBlog()}
                    disabled={!authenticated || !coreInstalled}
                  >
                    Установить
                  </button>
                )}
              </td>
            </tr>
            <tr data-testid="site-blog-panel">
              <td>
                Blog на сайте
                <div className="small muted">enable + mount /blog</div>
              </td>
              <td colSpan={2}>
                <select
                  id="enable-blog-site"
                  data-testid="enable-blog-site"
                  aria-label="Сайт для включения blog"
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
              </td>
              <td>
                {blogEnabledOnTarget ? (
                  <>Включён на сайте</>
                ) : (
                  <>
                    Выключен
                    <div className="small muted" data-testid="enable-blog-step-hint">
                      {enableBlogStepHint}
                    </div>
                  </>
                )}
              </td>
              <td>
                {blogEnabledOnTarget ? (
                  <span className="badge green">Включён</span>
                ) : (
                  <button
                    type="button"
                    className="btn primary"
                    data-testid="enable-blog"
                    onClick={() => void enableBlog()}
                    disabled={!authenticated || !blogInstalled}
                  >
                    Включить на сайте
                  </button>
                )}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <section className="card mt24" data-testid="module-blog-binding-panel">
        <h2 className="mb16">Привязка блога к странице</h2>
        <p className="small muted mb16">
          Публичный вход <code>/blog</code> и привязка к узлу дерева — на экране «Страницы» (preview move / bind).
        </p>
        <div className="row gap8 wrap">
          <Link className="btn" to="/pages">
            Дерево и привязка
          </Link>
        </div>
      </section>

      <div className="row between mt24 mb16">
        <h2 className="mb0">Каталог пакетов</h2>
        <button type="button" className="btn" data-testid="scan-incoming" onClick={() => void scanIncoming()} disabled={!authenticated}>
          Сканировать incoming
        </button>
      </div>

      <div className="table-card" data-testid="versions-panel">
        <div className="table-scroll">
          <table className="data">
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
      </div>
    </>
  );
}
