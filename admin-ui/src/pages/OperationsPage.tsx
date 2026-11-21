import { useEffect, useState } from 'react';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

const DONE_STATUSES = new Set(['succeeded', 'completed', 'success']);
const FAILED_STATUSES = new Set(['failed', 'error']);

function statusTone(status: string): string {
  if (DONE_STATUSES.has(status)) {
    return 'green';
  }
  if (FAILED_STATUSES.has(status)) {
    return 'red';
  }
  return 'amber';
}

type OperationRow = {
  id: string;
  operation_type: string;
  type_label: string;
  status_label: string;
  summary: string;
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
          <p>Журнал платформы: установка и обновление пакетов, включение модулей на сайтах. Время — МСК.</p>
        </div>
      </div>
      {loadError !== null && (
        <div className="notice warning" role="alert">
          {loadError}
        </div>
      )}
      <section className="card" id="operations" data-testid="operations-panel">
        {operations.length === 0 ? (
          <div className="empty-state">
            <p className="small muted">Операций в журнале пока нет.</p>
          </div>
        ) : (
          operations.map((row) => (
            <div className={`log-entry ${FAILED_STATUSES.has(row.status) ? 'failed' : ''}`} key={row.id} data-testid={`operation-row-${row.id.slice(0, 8)}`}>
              <span className="dot" />
              <div style={{ flex: 1 }}>
                <strong data-testid="operation-type">{row.type_label}</strong>
                <div className="muted">{row.summary}</div>
                {row.error_message !== null ? (
                  <div className="field-error">{row.error_message}</div>
                ) : null}
              </div>
              <div className="stack" style={{ gap: 4, alignItems: 'flex-end' }}>
                <span className={`badge ${statusTone(row.status)}`} data-testid="operation-status">
                  {row.status_label}
                </span>
                <span className="date-cell">{row.created_at}</span>
              </div>
            </div>
          ))
        )}
        <div className="notice mt24">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>Cron, очередь писем и DLQ появятся вместе с их API (этап 8); здесь — журнал операций платформы.</div>
        </div>
      </section>
      <AdminFooter />
    </>
  );
}
