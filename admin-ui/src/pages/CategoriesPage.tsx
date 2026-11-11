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
          <h2 className="mb16">Категории появятся в следующих этапах</h2>
          <p className="small muted">Макет: таблица категорий и форма создания.</p>
        </div>
      </section>
    </>
  );
}
