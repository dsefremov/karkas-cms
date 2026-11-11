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
      <section className="card" data-testid="dashboard-panel">
        <div className="empty-state">
          <h2 className="mb16">Сводка появится, когда API обзора будет в платформе</h2>
          <p className="small muted mb24">
            На этапе 7.5 нет эндпоинта агрегатов (статьи / страницы / активность). Используйте разделы «Записи», «Страницы»
            и «Операции».
          </p>
          <div className="row gap8 wrap">
            <Link className="btn" to="/posts">
              Записи
            </Link>
            <Link className="btn" to="/pages">
              Страницы
            </Link>
            <Link className="btn" to="/operations">
              Операции
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
