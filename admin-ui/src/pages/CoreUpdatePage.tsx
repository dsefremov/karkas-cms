import { useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';
import { packageLabel } from '../lib/packageLabels';
import type { PackageVersionRow } from '../types/admin';

/** Из manifest karkas/core 1.1.0 (requires: php ^8.4.0, mysql ^8.0.0 InnoDB, ext mbstring + pdo_mysql). */
const REQUIREMENTS = 'PHP ≥ 8.4 · MySQL 8.0/8.4 (InnoDB) · расширения mbstring, pdo_mysql';

type CompatState = { label: string; tone: 'ok' | 'update' | 'blocked' };

function compatState(row: PackageVersionRow): CompatState {
  if (row.status === 'incompatible' || row.status === 'blocked') {
    return { label: 'Несовместимо', tone: 'blocked' };
  }
  if (row.current !== null && row.available !== null && row.current !== row.available) {
    return { label: 'Нужно обновить', tone: 'update' };
  }
  return { label: 'OK', tone: 'ok' };
}

export function CoreUpdatePage(): React.JSX.Element {
  const {
    authenticated,
    versions,
    coreRow,
    corePlan,
    coreUpgradeAvailable,
    coreUpgradeConfirm,
    setCoreUpgradeConfirm,
    loadCorePlan,
    upgradeCore,
  } = useAdmin();

  useEffect(() => {
    if (authenticated) {
      void loadCorePlan();
    }
  }, [authenticated, loadCorePlan]);

  const moduleRows = useMemo(
    () => (versions?.packages ?? []).filter((row) => row.package_id !== 'karkas/core'),
    [versions?.packages],
  );
  // karkas/core в плане показывается одним шагом «Ядро current → target», не дублем среди модулей.
  const moduleTargets = useMemo(
    () => (corePlan?.plan?.targets ?? []).filter((target) => target.package_id !== 'karkas/core'),
    [corePlan?.plan?.targets],
  );
  const errors = corePlan?.errors ?? [];
  const current = coreRow?.current ?? null;
  const available = coreRow?.available ?? null;
  // plan.core_version — ТЕКУЩАЯ версия ядра; целевая — версия karkas/core среди targets плана.
  const targetCore =
    corePlan?.plan?.targets?.find((target) => target.package_id === 'karkas/core')?.version ?? available ?? '—';

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Обновление ядра</h1>
          <p>Каркас · версии ядра и модулей · один сайт</p>
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
          <strong data-testid="core-version-summary">{current ?? '—'}</strong>
        </div>
        <div className="kv">
          <span>Доступная версия ядра</span>
          <strong data-testid="core-version-available">{available ?? '—'}</strong>
        </div>
        <div className="kv">
          <span>Требования</span>
          <strong data-testid="core-requirements">{REQUIREMENTS}</strong>
        </div>
        <h2 className="mt24 mb16">Совместимость с Core {targetCore}</h2>
        <div className="table-card">
          <table className="data" data-testid="core-compat-table">
            <thead>
              <tr>
                <th scope="col">Модуль</th>
                <th scope="col">Установлено</th>
                <th scope="col">Требуется / поддержка</th>
                <th scope="col">Статус</th>
              </tr>
            </thead>
            <tbody>
              {moduleRows.length === 0 ? (
                <tr>
                  <td colSpan={4} className="muted">
                    Модули не установлены — проверять совместимость нечего.
                  </td>
                </tr>
              ) : (
                moduleRows.map((row) => {
                  const state = compatState(row);
                  return (
                    <tr key={row.package_id} data-testid={`compat-row-${row.package_id.replace('/', '-')}`}>
                      <td>
                        <strong>{packageLabel(row.package_id)}</strong>
                        <div className="sub">{row.package_id}</div>
                      </td>
                      <td>{row.current ?? '—'}</td>
                      <td>
                        {state.tone === 'update'
                          ? `Обновить до ${row.available ?? '—'} перед Core ${targetCore}`
                          : `Совместим с Core ${targetCore}`}
                      </td>
                      <td>
                        <span className={`compat-status ${state.tone}`}>{state.label}</span>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
        {errors.length > 0 ? (
          <div className="notice warning mt24" role="alert">
            <AdminIcon>{NAV_ICONS.operations}</AdminIcon>
            <div>Старт заблокирован: {errors.join(' ')}</div>
          </div>
        ) : null}
        <div className="notice mt24">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>
            Перед обновлением: повторное подтверждение пароля. На время обновления включается maintenance mode,
            сохраняется слот для отката; запись версий выполняется в одной транзакции БД.
          </div>
        </div>
      </section>
      <section className="card mb24" data-testid="core-plan-preview">
        <h2 className="mb16">План обновления · по порядку</h2>
        <p className="small muted mb16">
          Целевая версия Core: <strong data-testid="core-plan-target">{targetCore}</strong>
        </p>
        {coreUpgradeAvailable ? (
          <ol className="plan-steps" data-testid="core-plan-steps">
            <li>Проверить совместимость плана (ядро + модули, PHP/MySQL).</li>
            <li>Включить maintenance mode и сохранить слот для отката.</li>
            <li data-testid="plan-step-core">
              Обновить ядро: karkas/core {current} → {targetCore}
            </li>
            {moduleTargets.map((target) => (
              <li key={target.package_id} data-testid={`plan-step-${target.package_id.replace('/', '-')}`}>
                {packageLabel(target.package_id)} → {target.version}
              </li>
            ))}
            <li>Записать версии в одной транзакции и выключить maintenance mode.</li>
          </ol>
        ) : (
          <p className="small muted" data-testid="core-plan-empty">
            Шагов нет: ядро уже на версии {current ?? '—'}.
          </p>
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
            Подтверждаю обновление Core до {available}
          </label>
          <div className="row gap8 mt24 wrap">
            <button
              type="button"
              className="btn primary"
              data-testid="upgrade-core"
              onClick={() => void upgradeCore()}
              disabled={!authenticated || !coreUpgradeConfirm || errors.length > 0}
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
          Обновление не требуется: текущая версия совпадает с доступной.
        </p>
      )}
      <AdminFooter />
    </>
  );
}
