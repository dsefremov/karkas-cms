import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAdmin } from '../context/AdminContext';
import { useBlogPosts } from '../hooks/useBlogPosts';

export function PostEditorPage(): React.JSX.Element {
  const { postId } = useParams<{ postId?: string }>();
  const isNew = postId === undefined || postId === 'new';
  const navigate = useNavigate();
  const { authenticated, setMessage, setError } = useAdmin();
  const { loadPost, createPost, savePost } = useBlogPosts();
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [bodyText, setBodyText] = useState('');
  const [activity, setActivity] = useState<'active' | 'inactive'>('inactive');

  useEffect(() => {
    if (isNew || postId === undefined) {
      return;
    }
    void (async () => {
      const post = await loadPost(postId);
      if (post === null) {
        setError('Запись не найдена');
        return;
      }
      setTitle(post.title);
      setSlug(post.slug);
      setBodyText(post.body_text);
      setActivity(post.activity === 'active' ? 'active' : 'inactive');
    })();
  }, [isNew, postId, loadPost, setError]);

  async function handleSave(): Promise<void> {
    setError(null);
  if (isNew) {
      const id = await createPost({ title, slug, body_text: bodyText, activity });
      if (id === null) {
        setError('Не удалось создать запись');
        return;
      }
      setMessage('Черновик создан');
      navigate(`/posts/${id}`);
      return;
    }
    if (postId === undefined) {
      return;
    }
    const ok = await savePost(postId, { title, slug, body_text: bodyText, activity });
    if (!ok) {
      setError('Сохранение отклонено');
      return;
    }
    setMessage('Запись сохранена');
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1>{isNew ? 'Новая запись' : 'Редактор записи'}</h1>
          <p className="crumbs">
            <Link to="/posts">Записи</Link>
            {!isNew && postId !== undefined && (
              <>
                <span>/</span>
                <Link to={`/posts/${postId}/history`}>История</Link>
              </>
            )}
          </p>
        </div>
        <button type="button" className="btn primary" data-testid="post-editor-save" onClick={() => void handleSave()} disabled={!authenticated}>
          Сохранить
        </button>
      </div>
      <section className="card editor-sheet" data-testid="post-editor-panel">
        <div className="field">
          <label htmlFor="post-editor-title">Заголовок</label>
          <input id="post-editor-title" data-testid="post-editor-title" value={title} onChange={(e) => setTitle(e.target.value)} />
        </div>
        <div className="field">
          <label htmlFor="post-editor-slug">Slug</label>
          <input id="post-editor-slug" data-testid="post-editor-slug" value={slug} onChange={(e) => setSlug(e.target.value)} />
        </div>
        <div className="field">
          <label htmlFor="post-editor-body">Текст</label>
          <textarea id="post-editor-body" data-testid="post-editor-body" rows={12} value={bodyText} onChange={(e) => setBodyText(e.target.value)} />
        </div>
        <div className="field">
          <label htmlFor="post-editor-activity">Activity</label>
          <select
            id="post-editor-activity"
            data-testid="post-editor-activity"
            value={activity}
            onChange={(e) => setActivity(e.target.value === 'active' ? 'active' : 'inactive')}
          >
            <option value="inactive">inactive</option>
            <option value="active">active</option>
          </select>
        </div>
      </section>
    </>
  );
}
