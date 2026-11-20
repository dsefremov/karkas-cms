import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';
import { AdminFooter } from '../components/AdminFooter';
import { useBlogPosts } from '../hooks/useBlogPosts';
import { formatMskDateTime } from '../lib/formatMsk';

type ActivityFilter = 'all' | 'active' | 'inactive';
type SortKey = 'title' | 'updated' | 'activity';
type SortDir = 'asc' | 'desc';

const PAGE_SIZE = 10;
const AUTHOR_LABEL = 'Служебный админ';

const DEMO_CATEGORY: Record<string, string> = {
  'space-for-clarity': 'Мысли',
  'preserve-work-context': 'Процесс',
  'observe-to-notice': 'Наблюдение',
  'order-for-ideas': 'Практика',
};

type PostRow = {
  id: string;
  title: string;
  slug: string;
  activity: string;
  updated_at: string;
};

export function PostsPage(): React.JSX.Element {
  const { authenticated } = useAdmin();
  const { posts, loading, error, refresh } = useBlogPosts();
  const [query, setQuery] = useState('');
  const [filter, setFilter] = useState<ActivityFilter>('all');
  const [sortKey, setSortKey] = useState<SortKey>('updated');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [page, setPage] = useState(0);

  useEffect(() => {
    if (authenticated) {
      void refresh();
    }
  }, [authenticated, refresh]);

  useEffect(() => {
    setPage(0);
  }, [query, filter, sortKey, sortDir]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    return posts.filter((post) => {
      if (filter === 'active' && post.activity !== 'active') {
        return false;
      }
      if (filter === 'inactive' && post.activity === 'active') {
        return false;
      }
      if (q === '') {
        return true;
      }
      return post.title.toLowerCase().includes(q) || post.slug.toLowerCase().includes(q);
    });
  }, [posts, query, filter]);

  const sorted = useMemo(() => {
    const copy = [...filtered];
    copy.sort((a, b) => {
      let cmp = 0;
      if (sortKey === 'title') {
        cmp = a.title.localeCompare(b.title, 'ru');
      } else if (sortKey === 'activity') {
        cmp = a.activity.localeCompare(b.activity);
      } else {
        cmp = a.updated_at.localeCompare(b.updated_at);
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

      <div className="tabs">
        <button type="button" className={`tab ${filter === 'all' ? 'active' : ''}`} onClick={() => setFilter('all')}>
          Все статьи <span className="badge">{posts.length}</span>
        </button>
        <button
          type="button"
          className={`tab ${filter === 'active' ? 'active' : ''}`}
          onClick={() => setFilter('active')}
        >
          Активна {activeCount}
        </button>
        <button
          type="button"
          className={`tab ${filter === 'inactive' ? 'active' : ''}`}
          onClick={() => setFilter('inactive')}
        >
          Неактивна {inactiveCount}
        </button>
      </div>
      <div className="toolbar">
        <input
          className="input"
          aria-label="Поиск статей"
          placeholder="Поиск по заголовку…"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          data-testid="posts-search"
        />
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
              <h2 className="mb16">Пока нет записей</h2>
              <p className="small muted mb24">Создайте статью или включите blog на сайте в разделе «Модули».</p>
              <Link className="btn primary" to="/posts/new">
                Новая статья
              </Link>
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

function categoryForSlug(slug: string): string {
  return DEMO_CATEGORY[slug] ?? 'Общее';
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
  posts: PostRow[];
  total: number;
  filteredTotal: number;
  sortKey: SortKey;
  sortDir: SortDir;
  onSort: (key: SortKey) => void;
  page: number;
  pageCount: number;
  onPageChange: (page: number) => void;
}): React.JSX.Element {
  const sortMark = (key: SortKey): string => (sortKey === key ? (sortDir === 'asc' ? ' ↑' : ' ↓') : '');

  return (
    <div className="table-card">
      <table className="data">
        <thead>
          <tr>
            <th scope="col" className="sr-only">
              Выбор
            </th>
            <th scope="col">
              <button type="button" className="sort-header" onClick={() => onSort('title')}>
                Заголовок{sortMark('title')}
              </button>
            </th>
            <th scope="col">
              <button type="button" className="sort-header" onClick={() => onSort('activity')}>
                Активность{sortMark('activity')}
              </button>
            </th>
            <th scope="col">Категории</th>
            <th scope="col">Автор</th>
            <th scope="col">
              <button type="button" className="sort-header" onClick={() => onSort('updated')}>
                Изменена{sortMark('updated')}
              </button>
            </th>
            <th scope="col">Действия</th>
          </tr>
        </thead>
        <tbody>
          {posts.map((post) => (
            <tr key={post.id} data-testid={`post-row-${post.slug}`}>
              <td>
                <span className="box" />
              </td>
              <td>
                <Link className="title" to={`/posts/${post.id}`}>
                  {post.title}
                </Link>
                <span className="sub">/{post.slug}</span>
              </td>
              <td>
                <span className={`badge ${post.activity === 'active' ? 'green' : ''}`}>
                  {post.activity === 'active' ? 'Активна' : 'Неактивна'}
                </span>
              </td>
              <td className="muted">{categoryForSlug(post.slug)}</td>
              <td className="muted">{AUTHOR_LABEL}</td>
              <td className="date-cell">
                <strong>{formatMskDateTime(post.updated_at)}</strong>
              </td>
              <td>
                <Link className="btn quiet small" to={`/posts/${post.id}/history`} data-testid={`post-history-link-${post.slug}`}>
                  История
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <div className="table-footer row between wrap gap8">
        <span>
          {filteredTotal} из {total} · время МСК
        </span>
        <div className="row gap8">
          <button
            type="button"
            className="btn quiet small"
            disabled={page <= 0}
            onClick={() => onPageChange(page - 1)}
            data-testid="posts-page-prev"
          >
            Назад
          </button>
          <span className="small muted">
            {page + 1} / {pageCount}
          </span>
          <button
            type="button"
            className="btn quiet small"
            disabled={page >= pageCount - 1}
            onClick={() => onPageChange(page + 1)}
            data-testid="posts-page-next"
          >
            Вперёд
          </button>
        </div>
      </div>
    </div>
  );
}