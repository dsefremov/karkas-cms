import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { PageTreeRows } from '../components/page-tree/PageTreeRows';
import { TreeMoveModal } from '../components/page-tree/TreeMoveModal';
import { findPageNode } from '../lib/pageTree';
import { useAdmin } from '../context/AdminContext';
import { PAGE_TREE_ROOT } from '../types/admin';

export function PagesPage(): React.JSX.Element {
  const {
    authenticated,
    sites,
    pageTreeSiteId,
    setPageTreeSiteId,
    loadPageTree,
    pageTree,
    selectedPageId,
    pageTitle,
    selectPage,
    blogFacadeBoundPageId,
    moveParentId,
    setMoveParentId,
    moveSortOrder,
    setMoveSortOrder,
    movePathPreview,
    moveImpact,
    previewPageMove,
    confirmPageMove,
    createChildPage,
    seedDemoPages,
  } = useAdmin();

  const [moveModalOpen, setMoveModalOpen] = useState(false);
  const [newPageOpen, setNewPageOpen] = useState(false);
  const [newTitle, setNewTitle] = useState('');
  const [newSlug, setNewSlug] = useState('');
  const defaultSite = sites.find((s) => s.id === pageTreeSiteId);

  useEffect(() => {
    if (authenticated) {
      void loadPageTree();
    }
  }, [authenticated, pageTreeSiteId, loadPageTree]);

  const selected = selectedPageId !== null ? findPageNode(pageTree, selectedPageId) : undefined;
  const parentNode =
    selected !== undefined && selected.parent_id !== PAGE_TREE_ROOT
      ? findPageNode(pageTree, selected.parent_id)
      : undefined;

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Страницы</h1>
          <p>
            Структура сайта · {defaultSite?.label ?? '—'} · версия дерева из API
          </p>
        </div>
        <div className="row gap8">
          <button
            type="button"
            className="btn primary"
            data-testid="page-new"
            onClick={() => setNewPageOpen(true)}
            disabled={!authenticated}
          >
            <AdminIcon>{NAV_ICONS.plus}</AdminIcon>
            Новая страница
          </button>
        </div>
      </div>

      {sites.length > 1 ? (
        <div className="row gap8 mb24">
          <label className="small muted" htmlFor="page-tree-site">
            Сайт
          </label>
          <select
            id="page-tree-site"
            data-testid="page-tree-site"
            value={pageTreeSiteId}
            onChange={(e) => setPageTreeSiteId(Number(e.target.value))}
          >
            {sites.map((site) => (
              <option key={site.id} value={site.id}>
                {site.site_key} ({site.label})
              </option>
            ))}
          </select>
        </div>
      ) : null}

      <MotionMoveToolbar
        selectedTitle={selected?.title}
        moveModalOpen={moveModalOpen}
        setMoveModalOpen={setMoveModalOpen}
        disabled={!authenticated || selectedPageId === null}
      />

      <div className="tree-layout" data-testid="page-tree-panel">
        <section className="card tree-panel">
          <div className="tree-heading">
            <span>Название / вложенность / адрес</span>
            <span>Статус</span>
          </div>
          {pageTree.length === 0 ? (
            <div className="empty-state" style={{ margin: 24 }}>
              <p className="small muted mb16">Дерево пустое. Создайте страницы через «Новая страница» или e2e-seed.</p>
            </div>
          ) : (
            <PageTreeRows
              nodes={pageTree}
              blogBoundPageId={blogFacadeBoundPageId}
              selectedPageId={selectedPageId}
              onSelect={selectPage}
            />
          )}
        </section>

        <aside className="stack">
          <div className="card" data-testid="page-move-impact">
            <div className="overline accent mb16">Перед перемещением</div>
            {selected !== undefined ? (
              <>
                <h2 className="mb16">
                  {selected.title}
                  {parentNode !== undefined ? ` → ${parentNode.title}` : ''}
                </h2>
                <div className="kv">
                  <span>Новый родитель</span>
                  <strong>
                    {moveParentId === PAGE_TREE_ROOT
                      ? 'Корень сайта'
                      : (findPageNode(pageTree, moveParentId)?.title ?? '—')}
                  </strong>
                </div>
                <div className="kv">
                  <span>Затронуто адресов</span>
                  <strong>{moveImpact ?? '—'}</strong>
                </div>
                {movePathPreview !== null ? (
                  <>
                    <div className="kv">
                      <span>Адрес сейчас</span>
                      <code>{movePathPreview.from}</code>
                    </div>
                    <div className="kv">
                      <span>Адрес станет</span>
                      <code>{movePathPreview.to}</code>
                    </div>
                  </>
                ) : null}
              </>
            ) : (
              <p className="small muted">Выберите страницу в дереве или откройте «Переместить через форму».</p>
            )}
          </div>
          <div className="notice">
            <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
            <div>
              Адрес страницы — полный путь в дереве, без /p/. Перемещение меняет адрес страницы и всех потомков; с
              прежнего публичного адреса создаётся 301 (GET/HEAD). Узел с бейджем «Модуль: Блог» — PageBinding; настройка
              в{' '}
              <Link to="/modules/blog/binding">привязке модуля</Link>.
            </div>
          </div>
          <div className="notice warning">
            <AdminIcon>{NAV_ICONS.operations}</AdminIcon>
            <div>
              Если дерево изменится параллельно, покажем конфликт и предложим обновить структуру. Ввод не теряется.
            </div>
          </div>
        </aside>
      </div>

      <p className="mt24 small muted">
        Выберите страницу → «Переместить через форму» → родитель и позиция. Удаление родителя с дочерними страницами
        заблокировано.
      </p>
      <button type="button" className="sr-only" data-testid="page-tree-seed" onClick={() => void seedDemoPages()}>
        Seed e2e
      </button>

      <TreeMoveModal
        open={moveModalOpen}
        pageTree={pageTree}
        selectedPageId={selectedPageId}
        pageTitle={pageTitle}
        moveParentId={moveParentId}
        setMoveParentId={setMoveParentId}
        moveSortOrder={moveSortOrder}
        setMoveSortOrder={setMoveSortOrder}
        movePathPreview={movePathPreview}
        onClose={() => setMoveModalOpen(false)}
        onPreview={() => void previewPageMove()}
        onConfirm={async () => {
          await confirmPageMove();
          setMoveModalOpen(false);
        }}
      />

      {newPageOpen ? (
        <div className="modal-backdrop" data-testid="page-new-modal">
          <section className="modal" role="dialog" aria-label="Новая страница">
            <h2>Новая страница</h2>
            <div className="field">
              <label htmlFor="new-page-title">
                Заголовок
                <input
                  id="new-page-title"
                  value={newTitle}
                  onChange={(e) => setNewTitle(e.target.value)}
                />
              </label>
            </div>
            <div className="field">
              <label htmlFor="new-page-slug">
                Slug
                <input id="new-page-slug" value={newSlug} onChange={(e) => setNewSlug(e.target.value)} />
              </label>
            </div>
            <div className="row between mt24">
              <button type="button" className="btn" onClick={() => setNewPageOpen(false)}>
                Отмена
              </button>
              <button
                type="button"
                className="btn primary"
                onClick={() => {
                  void createChildPage(newTitle, newSlug, moveParentId).then(() => {
                    setNewPageOpen(false);
                    setNewTitle('');
                    setNewSlug('');
                  });
                }}
              >
                Создать
              </button>
            </div>
          </section>
        </div>
      ) : null}

      <AdminFooter />
    </>
  );
}

function MotionMoveToolbar({
  selectedTitle,
  moveModalOpen,
  setMoveModalOpen,
  disabled,
}: {
  selectedTitle: string | undefined;
  moveModalOpen: boolean;
  setMoveModalOpen: (open: boolean) => void;
  disabled: boolean;
}): React.JSX.Element {
  return (
    <div className="row between mb24">
      <div className="row gap8">
        {selectedTitle !== undefined ? (
          <span className="badge green">Выбрано: {selectedTitle}</span>
        ) : (
          <span className="small muted">Выберите узел в дереве</span>
        )}
      </div>
      <button
        type="button"
        className="btn"
        disabled={disabled}
        onClick={() => setMoveModalOpen(!moveModalOpen)}
      >
        <AdminIcon>{NAV_ICONS.pages}</AdminIcon>
        Переместить через форму
      </button>
    </div>
  );
}
