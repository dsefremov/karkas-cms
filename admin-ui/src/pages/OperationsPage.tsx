import { useEffect, useState } from 'react';
import { useAdmin } from '../context/AdminContext';

type OperationRow = {
  id: string;
  operation_type: string;
  status: string;
  phase: string | null;
  created_at: string;
  error_message: string | null;
};

export function OperationsPage(): React.JSX.Element {
  const { authenticated } = useAdmin();
  const [operations, setOperations] = useState<OperationRow[]>([]);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    if (!authenticated) {
      return;
    }
    void (async () => {
      const response = await fetch('/api/v1/admin/platform/operations');
      if (!response.ok) {
        setLoadError('Не удалось загрузить журнал операций');
        return;
      }
      const body = (await response.json()) as { operations: OperationRow[] };
      setOperations(body.operations);
    })();
  }, [authenticated]);

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Операции</h1>
          <p>Журнал платформенных операций (install / enable / upgrade).</p>
        </div>
      </div>
      {loadError !== null && (
        <div className="notice warning" role="alert">
          {loadError}
        </div>
      )}
      <section className="card table-card" id="operations" data-testid="operations-panel">
        {operations.length === 0 ? (
          <div className="empty-state">
            <p className="small muted">Операций в журнале пока нет.</p>
          </div>
        ) : (
          <table className="data">
            <thead>
              <tr>
                <th scope="col">Тип</th>
                <th scope="col">Статус</th>
                <th scope="col">Фаза</th>
                <th scope="col">Создано</th>
              </tr>
            </thead>
            <tbody>
              {operations.map((row) => (
                <tr key={row.id} data-testid={`operation-row-${row.id.slice(0, 8)}`}>
                  <td>{row.operation_type}</td>
                  <td>
                    <span className="badge outline">{row.status}</span>
                  </td>
                  <td>{row.phase ?? '—'}</td>
                  <td className="date-cell">{row.created_at}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <p className="small muted" style={{ padding: '16px' }}>
          Cron, очередь писем и DLQ — отдельного API нет (не показываем демо-цифры).
        </p>
      </section>
    </>
  );
}
