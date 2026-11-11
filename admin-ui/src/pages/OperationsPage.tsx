export function OperationsPage(): React.JSX.Element {
  return (
    <>
      <div className="page-head">
        <div>
          <h1>Операции</h1>
          <p>Фоновые задачи и очереди.</p>
        </div>
      </div>
      <section className="card" id="operations" data-testid="operations-panel">
        <h2>Фоновые операции</h2>
        <div className="kv">
          <span>Последний запуск cron</span>
          <strong>—</strong>
        </div>
        <div className="kv">
          <span>Поисковый индекс</span>
          <span className="badge green">Обновлён</span>
        </div>
        <div className="kv">
          <span>Писем в очереди</span>
          <strong>—</strong>
        </div>
        <div className="notice">
          <div>Принято в очередь ≠ доставлено. Состояние меняется после выполнения операции.</div>
        </div>
      </section>
    </>
  );
}
