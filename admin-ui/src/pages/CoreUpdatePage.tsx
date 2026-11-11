import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
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
    blogRow,
  } = useAdmin();

  useEffect(() => {
    if (authenticated) {
      void loadCorePlan();
    }
  }, [authenticated, loadCorePlan]);

  const targets = corePlan?.plan?.targets ?? [];
  const errors = corePlan?.errors ?? [];

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Обновление ядра</h1>
          <p>Мастер совместимости: целевая версия Core и зависимости модулей.</p>
        </div>
      </div>
      <section className="card mb24" data-testid="core-upgrade-panel">
        <div className="kv">
          <span>Текущая версия Core</span>
          <strong data-testid="core-version-summary">{coreRow?.current ?? '—'}</strong>
        </div>
        <div className="kv">
          <span>Доступная (локально)</span>
          <strong>{coreRow?.available ?? '—'}</strong>
        </div>
        {errors.length > 0 ? (
          <div className="notice warning mt24">
            <AdminIcon>{NAV_ICONS.operations}</AdminIcon>
            <div>{errors.join(' ')}</div>
          </div>
        ) : null}
      </section>
      <section className="table-card mb24">
        <table className="data" data-testid="core-plan-preview">
          <thead>
            <tr>
              <th scope="col">Модуль</th>
              <th scope="col">Требуется</th>
              <th scope="col">Статус</th>
            </tr>
          </thead>
          <tbody>
            <tr data-testid="version-row-karkas-core">
              <td>karkas/core</td>
              <td>{coreRow?.available ?? '—'}</td>
              <td>
                <span className="badge green">Core</span>
              </td>
            </tr>
            <tr>
              <td>karkas/blog</td>
              <td>{blogRow?.current ?? '—'}</td>
              <td>
                <span className="badge outline">{blogRow?.status ?? '—'}</span>
              </td>
            </tr>
            {targets.map((target) => (
              <tr key={target.package_id}>
                <td>{target.package_id}</td>
                <td>{target.version}</td>
                <td>
                  <span className="badge green">В плане</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
      {coreUpgradeAvailable ? (
        <section className="card">
          <h2 className="mb16">Подтверждение</h2>
          <label className="checkbox" htmlFor="core-upgrade-confirm">
            <input
              id="core-upgrade-confirm"
              data-testid="core-upgrade-confirm"
              type="checkbox"
              checked={coreUpgradeConfirm}
              onChange={(e) => setCoreUpgradeConfirm(e.target.checked)}
            />{' '}
            Подтверждаю обновление Core до {coreRow?.available}
          </label>
          <div className="row gap8 mt24">
            <button
              type="button"
              className="btn primary"
              data-testid="upgrade-core"
              onClick={() => void upgradeCore()}
              disabled={!authenticated || !coreUpgradeConfirm}
            >
              Начать обновление ядра
            </button>
            <Link className="btn" to="/modules">
              К модулям
            </Link>
          </div>
        </section>
      ) : (
        <p className="notice" data-testid="core-upgrade-idle">
          Обновление не требуется (текущая совпадает с локально доступной).
        </p>
      )}
      <VersionsPanel />
      <AdminFooter />
    </>
  );
}
