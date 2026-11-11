import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';
import { useBlogPosts } from '../hooks/useBlogPosts';

export function PostsPage(): React.JSX.Element {
  const { authenticated } = useAdmin();
  const { posts, loading, error, refresh } = useBlogPosts();

  useEffect(() => {
    if (authenticated) {
      void refresh();
    }
  }, [authenticated, refresh]);

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

      <PostsToolbar />

      <section className="article-list" data-testid="posts-list-panel">
        {loading ? (
          <div className="table-card">
            <p className="small muted" style={{ padding: 24 }}>
              Загрузка…
            </p>
          </div>
        ) : posts.length === 0 ? (
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
          <PostsTable posts={posts} />
        )}
      </section>

      <div className="notice mt24">
        <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
        <div>Редактирование и история версий — в карточке записи и разделе «История».</div>
      </div>
    </>
  );
}

function PostsToolbar(): React.JSX.Element {
  return (
    <div className="toolbar mb16">
      <input className="input" aria-label="Поиск статей" placeholder="Поиск по заголовку…" disabled />
      <button type="button" className="btn" disabled>
        Все категории
      </button>
      <button type="button" className="btn" disabled>
        Все авторы
      </button>
    </div>
  );
}

type PostRow = {
  id: string;
  title: string;
  slug: string;
  activity: string;
  updated_at: string;
};

function PostsTable({ posts }: { posts: PostRow[] }): React.JSX.Element {
  return (
    <div className="table-card">
      <table className="data">
        <thead>
          <tr>
            <th scope="col" className="sr-only">
              Выбор
            </th>
            <th scope="col">Заголовок</th>
            <th scope="col">Активность</th>
            <th scope="col">Категории</th>
            <th scope="col">Автор</th>
            <th scope="col">Изменена</th>
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
              <td className="muted">—</td>
              <td className="muted">—</td>
              <td className="date-cell">
                <strong>{post.updated_at}</strong>
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
      <div className="table-footer">
        <span>
          {posts.length} {posts.length === 1 ? 'запись' : 'записей'}
        </span>
      </div>
    </div>
  );
}
