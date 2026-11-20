import { useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { VersionsPanel } from '../components/VersionsPanel';
import { useAdmin } from '../context/AdminContext';

const REQUIREMENTS = 'PHP ≥ 8.4 · MySQL 8.0/8.4 · совместимые модули';

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

  const targets = useMemo(
    () =>
      (corePlan?.plan?.targets ?? []).filter(
        (target) => target.package_id !== 'karkas/core',
      ),
    [corePlan?.plan?.targets],
  );
  const errors = corePlan?.errors ?? [];
  const targetCore = corePlan?.plan?.core_version ?? coreRow?.available ?? '—';

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Обновление ядра</h1>
          <p>Мастер совместимости: целевая версия Core и зависимости модулей.</p>
        </div>
        <div className="row gap8">
          <Link className="btn" to="/modules">
            Модули
          </Link>
        </div>
      </div>
      <section className="card mb24" data-testid="core-upgrade-panel">
        <div className="kv">
          <span>Текущая версия ядра</span>
          <strong data-testid="core-version-summary">{coreRow?.current ?? '—'}</strong>
        </div>
        <div className="kv">
          <span>Доступная версия ядра</span>
          <strong data-testid="core-version-available">{coreRow?.available ?? '—'}</strong>
        </div>
        <div className="kv">
          <span>Требования</span>
          <strong>{REQUIREMENTS}</strong>
        </div>
        {errors.length > 0 ? (
          <div className="notice warning mt24">
            <AdminIcon>{NAV_ICONS.operations}</AdminIcon>
            <div>{errors.join(' ')}</div>
          </div>
        ) : null}
        <h2 className="mt24 mb16">Совместимость с Core {targetCore}</h2>
        <ButtonCompatTable
          blogRow={blogRow}
          targets={targets}
          coreAvailable={coreRow?.available}
        />
        <div className="notice mt24">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>
            Перед обновлением: резервная копия БД и файлов, повторное подтверждение пароля. Maintenance mode
            блокирует новые изменения.
          </div>
        </div>
      </section>
      <section className="card mb24" data-testid="core-plan-preview">
        <h2 className="mb16">План обновления · по порядку</h2>
        <p className="small muted mb16">
          Целевая версия Core: <strong>{targetCore}</strong>
          {coreRow?.current !== null && coreRow?.current !== undefined ? (
            <>
              {' '}
              (сейчас {coreRow.current})
            </>
          ) : null}
        </p>
        {targets.length === 0 ? (
          <p className="small muted">Нет дополнительных шагов в плане, кроме Core.</p>
        ) : (
          <ol className="small" style={{ lineHeight: 1.9, paddingLeft: 24 }}>
            <li>
              Обновить ядро karkas/core до {targetCore}
            </li>
            {targets.map((target) => (
              <li key={target.package_id}>
                {target.package_id} → {target.version}
              </li>
            ))}
          </ol>
        )}
      </section>
      {coreUpgradeAvailable ? (
        <section className="card mb24">
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

function ButtonCompatTable({
  blogRow,
  targets,
  coreAvailable,
}: {
  blogRow: { current: string | null; status: string } | undefined;
  targets: Array<{ package_id: string; version: string }>;
  coreAvailable: string | null | undefined;
}): React.JSX.Element {
  return (
    <div className="table-card">
      <table className="data">
        <thead>
          <tr>
            <th scope="col">Модуль</th>
            <th scope="col">Установлено</th>
            <th scope="col">Требуется / поддержка</th>
            <th scope="col">Статус</th>
          </tr>
        </thead>
        <tbody>
          {blogRow !== undefined ? (
            <tr>
              <td>karkas/blog</td>
              <td>{blogRow.current ?? '—'}</td>
              <td>План Core {coreAvailable ?? '—'}</td>
              <td>
                <span className="badge outline">{blogRow.status}</span>
              </td>
            </tr>
          ) : null}
          {targets.map((target) => (
            <tr key={target.package_id} data-testid={`plan-row-${target.package_id.replace('/', '-')}`}>
              <td>{target.package_id}</td>
              <td>—</td>
              <td>{target.version}</td>
              <td>
                <span className="badge green">В плане</span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}