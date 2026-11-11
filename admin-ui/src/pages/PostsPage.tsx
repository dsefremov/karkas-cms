import { useEffect } from 'react';
import { Link } from 'react-router-dom';
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
          <p>Список статей блога на сайте по умолчанию.</p>
        </div>
        <Link className="btn primary" to="/posts/new" data-testid="posts-new-link">
          Новая запись
        </Link>
      </div>
      {error !== null && (
        <div className="notice warning" data-testid="posts-list-error">
          {error}
        </div>
      )}
      <section className="card table-card article-list" data-testid="posts-list-panel">
        {loading ? (
          <p className="small muted">Загрузка…</p>
        ) : posts.length === 0 ? (
          <div className="empty-state">
            <p>Нет записей. Создайте первую или включите blog на сайте.</p>
          </div>
        ) : (
          <table className="data">
            <thead>
              <tr>
                <th scope="col">Заголовок</th>
                <th scope="col">Slug</th>
                <th scope="col">Activity</th>
                <th scope="col">Обновлено</th>
              </tr>
            </thead>
            <tbody>
              {posts.map((post) => (
                <tr key={post.id} data-testid={`post-row-${post.slug}`}>
                  <td>
                    <Link className="title" to={`/posts/${post.id}`}>
                      {post.title}
                    </Link>
                  </td>
                  <td>{post.slug}</td>
                  <td>
                    <span className={`badge ${post.activity === 'active' ? 'green' : ''}`}>{post.activity}</span>
                  </td>
                  <td className="date-cell">{post.updated_at}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>
      <section className="card" data-testid="site-blog-panel">
        <h2>Быстрые действия (e2e)</h2>
        <DemoPostActions />
      </section>
    </>
  );
}

function DemoPostActions(): React.JSX.Element {
  const { authenticated, lastPostId, blogHistory, saveDemoPost, loadBlogHistory } = useAdmin();

  return (
    <>
      <div className="actions actions-stack">
        <button type="button" className="btn" data-testid="blog-save-demo" onClick={() => void saveDemoPost()} disabled={!authenticated}>
          Сохранить демо-статью
        </button>
        <button type="button" className="btn" data-testid="blog-load-history" onClick={() => void loadBlogHistory()} disabled={!authenticated}>
          Показать историю версий
        </button>
      </div>
      {lastPostId !== null && (
        <p className="muted" data-testid="blog-last-post-id">
          Последний пост: {lastPostId}
        </p>
      )}
      {blogHistory.length > 0 && (
        <div className="table-scroll" data-testid="blog-history-panel">
          <table className="versions-table">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Заголовок</th>
                <th scope="col">Activity</th>
              </tr>
            </thead>
            <tbody>
              {blogHistory.map((row) => (
                <tr key={row.version_seq} data-testid={`blog-version-row-${row.version_seq}`}>
                  <td>{row.version_seq}</td>
                  <td>{row.title}</td>
                  <td>{row.activity}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}
