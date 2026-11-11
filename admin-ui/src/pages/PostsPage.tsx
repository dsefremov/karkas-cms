import { useAdmin } from '../context/AdminContext';

export function PostsPage(): React.JSX.Element {
  const { authenticated, lastPostId, blogHistory, saveDemoPost, loadBlogHistory } = useAdmin();

  return (
  <>
      <div className="page-head">
        <div>
          <h1>Записи</h1>
          <p>Демо-сохранение статей и история версий.</p>
        </div>
      </div>
      <section className="card" data-testid="site-blog-panel">
        <h2>Демо blog</h2>
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
      </section>
    </>
  );
}
