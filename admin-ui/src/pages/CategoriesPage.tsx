export function CategoriesPage(): React.JSX.Element {
  return (
    <>
      <div className="page-head">
        <div>
          <h1>Категории</h1>
          <p>Плоская таксономия записей.</p>
        </div>
      </div>
      <section className="card" data-testid="categories-panel">
        <div className="empty-state">
          <h2 className="mb16">API категорий в платформе отсутствует</h2>
          <p className="small muted">
            Макет v16 предусматривает таблицу категорий, но на этапах 3–7 нет <code>/api/v1/.../categories</code>. Раздел
            появится после появления API (этап 8+), не заглушка с фиктивными данными.
          </p>
        </div>
      </section>
    </>
  );
}
