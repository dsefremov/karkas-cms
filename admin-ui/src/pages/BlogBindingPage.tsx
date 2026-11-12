import { useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { PageTreeRows } from '../components/page-tree/PageTreeRows';
import { useAdmin } from '../context/AdminContext';

const MODE_COPY: Record<string, { title: string; lead: string }> = {
  binding: { title: 'Привязка к странице', lead: 'PageBinding: опубликованный узел дерева для /blog.' },
  unbind: { title: 'Отвязка: исход не выбран', lead: 'Выберите, куда вернуть страницу после отвязки модуля.' },
  'unbind-ready': { title: 'Отвязка: исход выбран', lead: 'Подтвердите отвязку блога от страницы.' },
  conflict: { title: 'Конфликт адреса', lead: 'Адрес занят или зарезервирован — сохранение недоступно.' },
  'address-change': { title: 'Смена адреса и редиректы', lead: 'План обязательных 301 при смене mount.' },
};

export function BlogBindingPage(): React.JSX.Element {
  const { pathname } = useLocation();
  const mode = pathname.split('/').pop() ?? 'binding';
  const copy = MODE_COPY[mode] ?? MODE_COPY.binding;
  const navigate = useNavigate();
  const {
    authenticated,
    pageTree,
    blogFacadeBoundPageId,
    selectedPageId,
    selectPage,
    bindBlogToSelectedPage,
    unbindBlogFacade,
    blogRow,
    sites,
    enableBlogSiteId,
    loadPageTree,
  } = useAdmin();
  const site = sites.find((s) => s.id === enableBlogSiteId);

  useEffect(() => {
    if (authenticated) {
      void loadPageTree();
    }
  }, [authenticated, loadPageTree]);

  return (
    <>
      <div className="module-breadcrumb">
        <Link to="/modules">Модули</Link> / Блог / {site?.label ?? 'Сайт'}
      </div>
      <div className="page-head">
        <div>
          <h1>Настройки модуля «Блог»</h1>
          <p>{copy.lead}</p>
        </div>
        <div className="row gap8 wrap">
          <Link className="btn" to="/modules/blog/address-change">
            План изменений
          </Link>
          {mode === 'binding' ? (
            <button
              type="button"
              className="btn primary"
              disabled={!authenticated || selectedPageId === null}
              onClick={() => void bindBlogToSelectedPage()}
              data-testid="blog-bind-page"
            >
              Сохранить привязку
            </button>
          ) : null}
          {mode === 'unbind' || mode === 'unbind-ready' ? (
            <button
              type="button"
              className={`btn ${mode === 'unbind-ready' ? 'primary' : ''}`}
              disabled={!authenticated || (mode === 'unbind' && blogFacadeBoundPageId === null)}
              onClick={() => void unbindBlogFacade()}
              data-testid="blog-unbind-page"
            >
              {mode === 'unbind-ready' ? 'Подтвердить отвязку' : 'Отвязать'}
            </button>
          ) : null}
        </div>
      </div>

      <div className="settings-two" data-testid="module-blog-binding-panel">
        <div className="stack">
          <section className="card">
            <h2 className="mb24">{copy.title}</h2>
            {mode === 'conflict' ? (
              <div className="error-panel mb24" data-testid="blog-bind-conflict">
                Конфликт: выбранный путь уже используется другой страницей или mount.
              </div>
            ) : null}
            <div className="segmented">
              <Link to="/modules/blog/settings">Собственный адрес</Link>
              <Link className="active" to="/modules/blog/binding">
                Страница из дерева
              </Link>
            </div>
            <p className="small muted mb16">
              Один instance, direct XOR page. Выберите опубликованный узел того же сайта.
            </p>
            <div className="mini-tree">
              {pageTree.length === 0 ? (
                <div className="small muted" style={{ padding: 16 }}>
                  Дерево пустое.{' '}
                  <button type="button" className="btn quiet" onClick={() => navigate('/pages')}>
                    Открыть страницы
                  </button>
                </div>
              ) : (
                <PageTreeRows
                  nodes={pageTree}
                  blogBoundPageId={blogFacadeBoundPageId}
                  selectedPageId={selectedPageId}
                  onSelect={selectPage}
                />
              )}
            </div>
            <div className="notice mt24">
              <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
              <div>
                Страница сохраняет владельца данных, UUID и body. Канонический вход модуля — <code>/blog</code>.
              </div>
            </div>
            <button type="button" className="btn mt24" onClick={() => navigate('/modules/blog/unbind')}>
              Отвязать · выбрать исход страницы
            </button>
          </section>
        </div>
        <aside className="stack">
          <section className="card">
            <div className="module-icon mb24">
              <AdminIcon>{NAV_ICONS.posts}</AdminIcon>
            </div>
            <h2 className="mb16">
              Блог <span className="small muted">{blogRow?.current ?? '—'}</span>
            </h2>
            <span className="badge green">Включён</span>
            <hr className="rule" />
            <div className="small muted mb8">SiteModule / instance</div>
            <strong>{site?.label ?? '—'}</strong>
            <div className="small muted">{site?.site_key ?? '—'}</div>
            <hr className="rule" />
            <div className="small muted mb8">Canonical mount</div>
            <code>/blog</code>
          </section>
          <section className="card">
            <h2 className="mb16">Связанные сценарии</h2>
            <div className="stack" style={{ gap: 12 }}>
              <Link className="accent small" to="/modules/blog/unbind">
                План отвязки →
              </Link>
              <Link className="accent small" to="/modules/blog/conflict">
                Конфликт адреса →
              </Link>
              <Link className="accent small" to="/modules/blog/address-change">
                Смена адреса и 301 →
              </Link>
            </div>
          </section>
        </aside>
      </div>
      <AdminFooter />
    </>
  );
}
