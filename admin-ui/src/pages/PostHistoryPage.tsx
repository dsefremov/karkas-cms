import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import type { BlogVersionRow } from '../types/admin';
import { useAdmin } from '../context/AdminContext';
import { useBlogPosts } from '../hooks/useBlogPosts';

export function PostHistoryPage(): React.JSX.Element {
  const { postId } = useParams<{ postId: string }>();
  const { authenticated, setMessage, setError } = useAdmin();
  const { loadHistory, restoreVersion } = useBlogPosts();
  const [history, setHistory] = useState<BlogVersionRow[]>([]);

  useEffect(() => {
    if (postId === undefined) {
      return;
    }
    void (async () => {
      setHistory(await loadHistory(postId));
    })();
  }, [postId, loadHistory]);

  async function restore(versionSeq: number): Promise<void> {
    if (postId === undefined) {
      return;
    }
    setError(null);
    const ok = await restoreVersion(postId, versionSeq);
    if (!ok) {
      setError('Восстановление версии отклонено');
      return;
    }
    setMessage(`Восстановлена версия #${versionSeq}`);
    setHistory(await loadHistory(postId));
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>История версий</h1>
          <p className="crumbs">
            <Link to="/posts">Записи</Link>
            {postId !== undefined && (
              <>
                <span>/</span>
                <Link to={`/posts/${postId}`}>Редактор</Link>
              </>
            )}
          </p>
        </div>
      </div>
      <section className="card table-card" data-testid="post-history-panel">
        <table className="data history-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Заголовок</th>
              <th scope="col">Activity</th>
              <th scope="col">Сохранено</th>
              <th scope="col" />
            </tr>
          </thead>
          <tbody>
            {history.map((row) => (
              <tr key={row.version_seq} data-testid={`blog-version-row-${row.version_seq}`}>
                <td>{row.version_seq}</td>
                <td>{row.title}</td>
                <td>{row.activity}</td>
                <td className="date-cell">{row.saved_at}</td>
                <td>
                  <button
                    type="button"
                    className="btn"
                    data-testid={`post-restore-${row.version_seq}`}
                    disabled={!authenticated}
                    onClick={() => void restore(row.version_seq)}
                  >
                    Восстановить
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </>
  );
}
