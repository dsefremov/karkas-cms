import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';

type DashboardPayload = {
  posts_active: number;
  posts_inactive: number;
  posts_changed_today: number;
  pages_count: number;
  recent_posts: Array<{
    id: string;
    title: string;
    activity_label: string;
    updated_at: string;
  }>;
  recent_operations: Array<{
    id: string;
    summary: string;
    type_label: string;
    status: string;
    created_at: string;
  }>;
};

export function DashboardPage(): React.JSX.Element {
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void (async () => {
      const response = await fetch('/api/v1/admin/dashboard');
      if (!response.ok) {
        setError('Сводка недоступна (включите blog на сайте по умолчанию).');
        return;
      }
      setData((await response.json()) as DashboardPayload);
    })();
  }, []);

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Хорошего дня.</h1>
          <p>Статьи, последние изменения и состояние сайта.</p>
        </div>
        <div className="row gap8">
          <a className="btn" href="/" target="_blank" rel="noreferrer">
            <AdminIcon>{NAV_ICONS.external}</AdminIcon>
            Открыть сайт
          </a>
          <Link className="btn primary" to="/posts/new">
            <AdminIcon>{NAV_ICONS.plus}</AdminIcon>
            Новая статья
          </Link>
        </div>
      </div>

      {error !== null ? (
        <div className="notice warning mb24" data-testid="dashboard-panel">
          {error}
        </div>
      ) : null}

      {data !== null ? (
        <section data-testid="dashboard-panel">
          <div className="stats">
            <StatCard label="Активные статьи" value={data.posts_active} hint="Видны на сайте" icon={NAV_ICONS.posts} />
            <StatCard
              label="Неактивные статьи"
              value={data.posts_inactive}
              hint="Сохранены, не видны на сайте"
              icon={NAV_ICONS.categories}
            />
            <StatCard
              label="Изменено сегодня"
              value={data.posts_changed_today}
              hint="Статьи всех авторов"
              icon={NAV_ICONS.operations}
            />
            <StatCard label="Страницы" value={data.pages_count} hint="В дереве сайта" icon={NAV_ICONS.pages} />
          </div>

          <div className="admin-grid">
            <div className="stack">
              <section className="card">
                <div className="row between mb16">
                  <h2>Последние изменения статей</h2>
                  <Link className="accent small" to="/posts">
                    Все записи →
                  </Link>
                </div>
                {data.recent_posts.length === 0 ? (
                  <p className="small muted">Записей пока нет.</p>
                ) : (
                  data.recent_posts.map((post) => (
                    <div className="log-entry" key={post.id}>
                      <span className="avatar">СА</span>
                      <div style={{ flex: 1 }}>
                        <Link to={`/posts/${post.id}`}>
                          <strong>{post.title}</strong>
                        </Link>
                        <div className="small muted">{post.updated_at}</div>
                      </div>
                      <span className={`badge ${post.activity_label === 'Активна' ? 'green' : ''}`}>
                        {post.activity_label}
                      </span>
                    </div>
                  ))
                )}
              </section>
              <section className="card">
                <h2 className="mb16">Последние действия</h2>
                {data.recent_operations.length === 0 ? (
                  <p className="small muted">Журнал операций пуст.</p>
                ) : (
                  data.recent_operations.map((op) => (
                    <div className="log-entry" key={op.id}>
                      <span className="dot" />
                      <div>
                        <strong>{op.type_label}</strong>
                        <div className="muted">
                          {op.summary} · {op.created_at}
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </section>
            </div>
            <div className="stack">
              <section className="card" id="operations">
                <div className="row between mb16">
                  <h2>Фоновые операции</h2>
                  <Link className="accent small" to="/operations">
                    Журнал →
                  </Link>
                </div>
                {data.recent_operations.slice(0, 3).map((op) => (
                  <div className="kv" key={op.id}>
                    <span>{op.type_label}</span>
                    <span className="badge outline">{op.status}</span>
                  </div>
                ))}
                <div className="notice mt24">
                  <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
                  <div>Cron, очередь писем и DLQ — отдельного API нет; показаны записи журнала платформы.</div>
                </div>
              </section>
              <section className="card">
                <h2 className="mb16">История статей</h2>
                <p className="small muted">
                  Хранятся последние 10 версий каждой статьи. Более старые удаляются автоматически.
                </p>
                {data.recent_posts[0] !== undefined ? (
                  <div className="mt24">
                    <Link className="btn" to={`/posts/${data.recent_posts[0].id}/history`}>
                      Открыть историю записи
                    </Link>
                  </div>
                ) : null}
              </section>
            </div>
          </div>
        </section>
      ) : null}

      <AdminFooter />
    </>
  );
}

function StatCard({
  label,
  value,
  hint,
  icon,
}: {
  label: string;
  value: number;
  hint: string;
  icon: React.ReactNode;
}): React.JSX.Element {
  return (
    <div className="stat">
      <div className="row between">
        <span className="label">{label}</span>
        <AdminIcon>{icon}</AdminIcon>
      </div>
      <div className="value">{value}</div>
      <div className="small muted">{hint}</div>
    </div>
  );
}
