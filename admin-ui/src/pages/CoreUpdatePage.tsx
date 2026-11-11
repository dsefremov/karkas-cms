import { VersionsPanel } from '../components/VersionsPanel';
import { useAdmin } from '../context/AdminContext';

export function CoreUpdatePage(): React.JSX.Element {
  const {
    authenticated,
    coreRow,
    corePlan,
    coreUpgradeAvailable,
    coreUpgradeConfirm,
    setCoreUpgradeConfirm,
    loadCorePlan,
    upgradeCore,
  } = useAdmin();

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Обновление Core</h1>
          <p>План и явное обновление ядра.</p>
        </div>
      </div>
      <section className="card" data-testid="core-upgrade-panel">
        <h2>Обновление Core</h2>
        {coreRow !== undefined ? (
          <p className="muted" data-testid="core-version-summary">
            Текущая: <strong>{coreRow.current ?? '—'}</strong> → доступная (локально):{' '}
            <strong>{coreRow.available ?? '—'}</strong>
          </p>
        ) : (
          <p className="muted">Данные Core недоступны</p>
        )}
        <div className="actions actions-stack">
          <button type="button" className="btn" data-testid="load-core-plan" onClick={() => void loadCorePlan()} disabled={!authenticated}>
            Показать план обновления Core
          </button>
        </div>
        {corePlan !== null && (
          <pre className="plan-preview" data-testid="core-plan-preview">
            {JSON.stringify(corePlan.plan?.targets ?? [], null, 2)}
          </pre>
        )}
        {coreUpgradeAvailable ? (
          <>
            <div className="form-row">
              <label htmlFor="core-upgrade-confirm">
                <input
                  id="core-upgrade-confirm"
                  data-testid="core-upgrade-confirm"
                  type="checkbox"
                  checked={coreUpgradeConfirm}
                  onChange={(e) => setCoreUpgradeConfirm(e.target.checked)}
                />{' '}
                Подтверждаю явное обновление Core до {coreRow?.available}
              </label>
            </div>
            <div className="actions actions-stack">
              <button
                type="button"
                className="btn primary"
                data-testid="upgrade-core"
                onClick={() => void upgradeCore()}
                disabled={!authenticated || !coreUpgradeConfirm}
              >
                Обновить Core (include_core)
              </button>
            </div>
          </>
        ) : (
          <p className="muted">Обновление не требуется (текущая совпадает с локально доступной).</p>
        )}
      </section>
      <VersionsPanel />
    </>
  );
}
