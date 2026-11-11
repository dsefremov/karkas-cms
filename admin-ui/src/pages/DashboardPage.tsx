import { Link } from 'react-router-dom';

export function DashboardPage(): React.JSX.Element {
  return (
    <>
      <div className="page-head">
        <div>
          <h1>Обзор</h1>
          <p>Статьи, последние изменения и состояние сайта.</p>
        </div>
      </div>
      <div className="stats" data-testid="dashboard-panel">
        <div className="stat">
          <span className="label">Активные статьи</span>
          <div className="value">—</div>
          <div className="small muted">Видны на сайте</div>
        </div>
        <div className="stat">
          <span className="label">Неактивные статьи</span>
          <div className="value">—</div>
          <div className="small muted">Сохранены, не видны на сайте</div>
        </div>
        <div className="stat">
          <span className="label">Изменено сегодня</span>
          <div className="value">—</div>
          <div className="small muted">Статьи всех авторов</div>
        </div>
        <div className="stat">
          <span className="label">Страницы</span>
          <div className="value">—</div>
          <div className="small muted">В дереве сайта</div>
        </div>
      </div>
      <section className="card" id="operations">
        <div className="row between mb16">
          <h2>Фоновые операции</h2>
          <Link className="btn" to="/operations">
            Подробнее
          </Link>
        </div>
        <p className="small muted">Состояние очередей и фоновых задач — см. раздел «Операции».</p>
      </section>
    </>
  );
}
