import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { AdminSelect } from '../components/AdminSelect';
import { useAdmin } from '../context/AdminContext';
import { AdminFooter } from '../components/AdminFooter';
import { useBlogPosts, type BlogPostRow } from '../hooks/useBlogPosts';
import { formatMskParts, utcSortKey } from '../lib/formatMsk';

type ActivityFilter = 'all' | 'active' | 'inactive';
type SortKey = 'created' | 'updated' | 'title';
type SortDir = 'asc' | 'desc';

const PAGE_SIZE = 10;
/**
 * На этапе 7.5 записи создаёт только служебный админ (одна учётная запись, API пользователей — этап 8),
 * а таксономии категорий в схеме блога ещё нет. Поэтому значения ниже — реальные, а не демо-подстановка.
 */
const AUTHOR_LABEL = 'Служебный админ';
const NO_CATEGORY = 'Без категории';

const SORT_OPTIONS: Array<{ value: SortKey; label: string }> = [
  { value: 'created', label: 'Создана' },
  { value: 'updated', label: 'Изменена' },
  { value: 'title', label: 'Заголовок' },
];

function postCategory(post: BlogPostRow): string {
  return post.category ?? NO_CATEGORY;
}

function postAuthor(post: BlogPostRow): string {
  return post.author ?? AUTHOR_LABEL;
}

export function PostsPage(): React.JSX.Element {
  const { authenticated } = useAdmin();
  const { posts, loading, error, refresh } = useBlogPosts();
  const [query, setQuery] = useState('');
  const [filter, setFilter] = useState<ActivityFilter>('all');
  const [category, setCategory] = useState('all');
  const [author, setAuthor] = useState('all');
  const [sortKey, setSortKey] = useState<SortKey>('created');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [page, setPage] = useState(0);

  useEffect(() => {
    if (authenticated) {
      void refresh();
    }
  }, [authenticated, refresh]);

  useEffect(() => {
    setPage(0);
  }, [query, filter, category, author, sortKey, sortDir]);

  const categoryOptions = useMemo(
    () => [
      { value: 'all', label: 'Все категории' },
      ...[...new Set(posts.map(postCategory))].sort((a, b) => a.localeCompare(b, 'ru')).map((c) => ({ value: c, label: c })),
    ],
    [posts],
  );
  const authorOptions = useMemo(
    () => [
      { value: 'all', label: 'Все авторы' },
      ...[...new Set(posts.map(postAuthor))].sort((a, b) => a.localeCompare(b, 'ru')).map((a) => ({ value: a, label: a })),
    ],
    [posts],
  );

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    return posts.filter((post) => {
      if (filter === 'active' && post.activity !== 'active') {
        return false;
      }
      if (filter === 'inactive' && post.activity === 'active') {
        return false;
      }
      if (category !== 'all' && postCategory(post) !== category) {
        return false;
      }
      if (author !== 'all' && postAuthor(post) !== author) {
        return false;
      }
      if (q === '') {
        return true;
      }
      return post.title.toLowerCase().includes(q) || post.slug.toLowerCase().includes(q);
    });
  }, [posts, query, filter, category, author]);

  const sorted = useMemo(() => {
    const copy = [...filtered];
    copy.sort((a, b) => {
      let cmp = 0;
      if (sortKey === 'title') {
        cmp = a.title.localeCompare(b.title, 'ru');
      } else if (sortKey === 'created') {
        cmp = utcSortKey(a.created_at ?? a.updated_at).localeCompare(utcSortKey(b.created_at ?? b.updated_at));
      } else {
        cmp = utcSortKey(a.updated_at).localeCompare(utcSortKey(b.updated_at));
      }
      return sortDir === 'asc' ? cmp : -cmp;
    });
    return copy;
  }, [filtered, sortKey, sortDir]);

  const pageCount = Math.max(1, Math.ceil(sorted.length / PAGE_SIZE));
  const pageSafe = Math.min(page, pageCount - 1);
  const slice = sorted.slice(pageSafe * PAGE_SIZE, pageSafe * PAGE_SIZE + PAGE_SIZE);

  const activeCount = posts.filter((p) => p.activity === 'active').length;
  const inactiveCount = posts.length - activeCount;

  function toggleSort(key: SortKey): void {
    if (sortKey === key) {
      setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortKey(key);
      setSortDir(key === 'title' ? 'asc' : 'desc');
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Записи</h1>
          <p>Активность статьи определяет её видимость на сайте.</p>
        </div>
        <div className="row gap8">
          <Link className="btn primary" to="/posts/new" data-testid="posts-new-link">
            <AdminIcon>{NAV_ICONS.plus}</AdminIcon>
            Новая статья
          </Link>
        </div>
      </div>

      {error !== null && (
        <div className="notice warning" data-testid="posts-list-error">
          {error}
        </div>
      )}

      <div className="tabs" role="tablist" aria-label="Фильтр по активности">
        <button
          type="button"
          role="tab"
          aria-selected={filter === 'all'}
          className={`tab ${filter === 'all' ? 'active' : ''}`}
          onClick={() => setFilter('all')}
          data-testid="posts-tab-all"
        >
          Все статьи <span className="badge">{posts.length}</span>
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={filter === 'active'}
          className={`tab ${filter === 'active' ? 'active' : ''}`}
          onClick={() => setFilter('active')}
          data-testid="posts-tab-active"
        >
          Активна {activeCount}
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={filter === 'inactive'}
          className={`tab ${filter === 'inactive' ? 'active' : ''}`}
          onClick={() => setFilter('inactive')}
          data-testid="posts-tab-inactive"
        >
          Неактивна {inactiveCount}
        </button>
      </div>
      <div className="toolbar posts-toolbar">
        <input
          className="input"
          aria-label="Поиск статей"
          placeholder="Поиск по заголовку…"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          data-testid="posts-search"
        />
        <AdminSelect
          id="posts-filter-category"
          testId="posts-filter-category"
          aria-label="Фильтр по категории"
          className="inline"
          value={category}
          onChange={setCategory}
          options={categoryOptions}
        />
        <AdminSelect
          id="posts-filter-author"
          testId="posts-filter-author"
          aria-label="Фильтр по автору"
          className="inline"
          value={author}
          onChange={setAuthor}
          options={authorOptions}
        />
      </div>
      <div className="posts-sortbar">
        <span className="small muted">Сортировка</span>
        <div className="segmented" role="group" aria-label="Сортировка">
          {SORT_OPTIONS.map((option) => (
            <button
              key={option.value}
              type="button"
              className={sortKey === option.value ? 'active' : ''}
              aria-pressed={sortKey === option.value}
              onClick={() => toggleSort(option.value)}
              data-testid={`posts-sort-${option.value}`}
            >
              {option.label}
              {sortKey === option.value ? (sortDir === 'asc' ? ' ↑' : ' ↓') : ''}
            </button>
          ))}
        </div>
        <span className="small muted posts-sort-hint">Нажмите заголовок, дату или повторно — сменить направление</span>
      </div>

      <section className="article-list" data-testid="posts-list-panel">
        {loading ? (
          <div className="table-card">
            <p className="small muted" style={{ padding: 24 }}>
              Загрузка…
            </p>
          </div>
        ) : sorted.length === 0 ? (
          <div className="table-card">
            <div className="empty-state">
              <h2 className="mb16">{posts.length === 0 ? 'Пока нет записей' : 'Ничего не найдено'}</h2>
              <p className="small muted mb24">
                {posts.length === 0
                  ? 'Создайте статью или включите blog на сайте в разделе «Модули».'
                  : 'Измените поиск или фильтры.'}
              </p>
              {posts.length === 0 ? (
                <Link className="btn primary" to="/posts/new">
                  Новая статья
                </Link>
              ) : null}
            </div>
          </div>
        ) : (
          <PostsTable
            posts={slice}
            total={posts.length}
            filteredTotal={sorted.length}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={toggleSort}
            page={pageSafe}
            pageCount={pageCount}
            onPageChange={setPage}
          />
        )}
      </section>

      <div className="notice mt24">
        <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
        <div>Редактирование и история версий — в карточке записи и разделе «История».</div>
      </div>
      <AdminFooter />
    </>
  );
}

function DateCell({ raw, by }: { raw: string | undefined; by: string }): React.JSX.Element {
  const parts = formatMskParts(raw ?? '');
  if (parts === null) {
    return <span className="muted">—</span>;
  }
  return (
    <>
      <strong>{parts.date}</strong>
      <span className="sub">{parts.time}</span>
      <span className="sub">{by}</span>
    </>
  );
}

function PostsTable({
  posts,
  total,
  filteredTotal,
  sortKey,
  sortDir,
  onSort,
  page,
  pageCount,
  onPageChange,
}: {
  posts: BlogPostRow[];
  total: number;
  filteredTotal: number;
  sortKey: SortKey;
  sortDir: SortDir;
  onSort: (key: SortKey) => void;
  page: number;
  pageCount: number;
  onPageChange: (page: number) => void;
}): React.JSX.Element {
  const ariaSort = (key: SortKey): 'ascending' | 'descending' | 'none' =>
    sortKey === key ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none';
  const sortMark = (key: SortKey): string => (sortKey === key ? (sortDir === 'asc' ? ' ↑' : ' ↓') : '');

  return (
    <div className="table-card posts-table">
      <table className="data">
        <thead>
          <tr>
            <th scope="col" aria-sort={ariaSort('title')}>
              <button type="button" className="sort-header" onClick={() => onSort('title')}>
                Заголовок{sortMark('title')}
              </button>
            </th>
            <th scope="col">Активность</th>
            <th scope="col">Категория</th>
            <th scope="col">Автор</th>
            <th scope="col" aria-sort={ariaSort('created')}>
              <button type="button" className="sort-header" onClick={() => onSort('created')} data-testid="posts-sort-created-header">
                Создана{sortMark('created')}
              </button>
            </th>
            <th scope="col" aria-sort={ariaSort('updated')}>
              <button type="button" className="sort-header" onClick={() => onSort('updated')}>
                Изменена{sortMark('updated')}
              </button>
            </th>
            <th scope="col">
              <span className="sr-only">Действия</span>
            </th>
          </tr>
        </thead>
        <tbody>
          {posts.map((post) => (
            <tr key={post.id} data-testid={`post-row-${post.slug}`}>
              <td data-label="Заголовок">
                <Link className="title" to={`/posts/${post.id}`}>
                  {post.title}
                </Link>
                <span className="sub">/{post.slug}</span>
              </td>
              <td data-label="Активность">
                <span className={`badge ${post.activity === 'active' ? 'green' : ''}`}>
                  {post.activity === 'active' ? 'Активна' : 'Неактивна'}
                </span>
              </td>
              <td data-label="Категория" className="muted">
                {postCategory(post)}
              </td>
              <td data-label="Автор">{postAuthor(post)}</td>
              <td data-label="Создана" className="date-cell" data-testid={`post-created-${post.slug}`}>
                <DateCell raw={post.created_at} by={postAuthor(post)} />
              </td>
              <td data-label="Изменена" className="date-cell" data-testid={`post-updated-${post.slug}`}>
                <DateCell raw={post.updated_at} by={postAuthor(post)} />
              </td>
              <td data-label="Действия">
                <Link className="btn quiet small" to={`/posts/${post.id}/history`} data-testid={`post-history-link-${post.slug}`}>
                  История
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <div className="table-footer row between wrap gap8">
        <span data-testid="posts-count">
          {filteredTotal === 0
            ? 'Нет записей'
            : `${page * PAGE_SIZE + 1}–${page * PAGE_SIZE + posts.length} из ${filteredTotal}`}
          {filteredTotal !== total ? ` (всего ${total})` : ''} · даты и время МСК
        </span>
        <nav className="row gap8 pager" aria-label="Страницы списка">
          <button
            type="button"
            className="btn small"
            disabled={page <= 0}
            onClick={() => onPageChange(page - 1)}
            data-testid="posts-page-prev"
          >
            Назад
          </button>
          <span className="pager-current" data-testid="posts-page-indicator">
            {page + 1} / {pageCount}
          </span>
          <button
            type="button"
            className="btn small"
            disabled={page >= pageCount - 1}
            onClick={() => onPageChange(page + 1)}
            data-testid="posts-page-next"
          >
            Далее
          </button>
        </nav>
      </div>
    </div>
  );
}
