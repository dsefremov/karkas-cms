import { useAdmin } from '../context/AdminContext';
import { PAGE_TREE_ROOT } from '../types/admin';

export function PagesPage(): React.JSX.Element {
  const {
    authenticated,
    sites,
    pageTreeSiteId,
    setPageTreeSiteId,
    loadPageTree,
    seedDemoPages,
    renderTreeNodes,
    pageTree,
    selectedPageId,
    pageTitle,
    setPageTitle,
    pageSlug,
    setPageSlug,
    moveParentId,
    setMoveParentId,
    moveImpact,
    blogFacadeBoundPageId,
    blogFacadeTreePath,
    bindBlogToSelectedPage,
    unbindBlogFacade,
    moveSelectedSibling,
    previewPageMove,
    confirmPageMove,
    saveSelectedPage,
  } = useAdmin();

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Страницы</h1>
          <p>Дерево страниц, перенос и привязка блога.</p>
        </div>
      </div>
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
          <button type="button" className="btn" data-testid="page-tree-refresh" onClick={() => void loadPageTree()} disabled={!authenticated}>
            Обновить дерево
          </button>
          <button type="button" className="btn" data-testid="page-tree-seed" onClick={() => void seedDemoPages()} disabled={!authenticated}>
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
            <p className="muted" data-testid="blog-facade-status">
              {blogFacadeBoundPageId
                ? `Блог привязан к странице (${blogFacadeTreePath ?? 'путь'} → /blog).`
                : blogFacadeTreePath
                  ? `Блог отвязан: /blog → /${blogFacadeTreePath}.`
                  : 'Блог: прямой вход /blog (без фасада страницы).'}
            </p>
            <div className="actions actions-stack">
              <button
                type="button"
                className="btn"
                data-testid="blog-bind-page"
                onClick={() => void bindBlogToSelectedPage()}
                disabled={!authenticated || selectedPageId === null}
              >
                Привязать блог к странице
              </button>
              <button
                type="button"
                className="btn"
                data-testid="blog-unbind-page"
                onClick={() => void unbindBlogFacade()}
                disabled={!authenticated || !blogFacadeBoundPageId}
              >
                Отвязать блог
              </button>
              <button type="button" className="btn" data-testid="page-tree-key-up" onClick={() => void moveSelectedSibling(-1)} disabled={!authenticated}>
                ↑ Выше (клавиатура)
              </button>
              <button type="button" className="btn" data-testid="page-tree-key-down" onClick={() => void moveSelectedSibling(1)} disabled={!authenticated}>
                ↓ Ниже (клавиатура)
              </button>
              <button type="button" className="btn" data-testid="page-move-preview" onClick={() => void previewPageMove()} disabled={!authenticated}>
                Предпросмотр переноса
              </button>
              <button type="button" className="btn" data-testid="page-move-confirm" onClick={() => void confirmPageMove()} disabled={!authenticated}>
                Подтвердить перенос
              </button>
              <button type="button" className="btn" data-testid="page-save" onClick={() => void saveSelectedPage()} disabled={!authenticated}>
                Сохранить страницу
              </button>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
