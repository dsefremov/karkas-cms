export function App(): React.JSX.Element {
  return (
    <div className="shell" data-testid="admin-shell" role="document">
      <aside className="sidebar" aria-label="Навигация">
        <div className="brand">Каркас</div>
        <nav>
          <span className="nav-item active">Обзор</span>
          <span className="nav-item muted">Контент</span>
          <span className="nav-item muted">Модули</span>
        </nav>
      </aside>
      <main className="content">
        <header className="topbar">
          <h1>Админка — этап 0</h1>
          <p className="subtitle">Статическая оболочка React; функции CMS в следующих этапах.</p>
        </header>
        <section className="card" aria-labelledby="status-heading">
          <h2 id="status-heading">Инфраструктура</h2>
          <ul>
            <li>Symfony 7.4 LTS backend</li>
            <li>Сборка фронтенда в CI, без Node на shared-хостинге</li>
            <li>MySQL 8.0 / 8.4 в матрице тестов</li>
          </ul>
        </section>
      </main>
    </div>
  );
}
