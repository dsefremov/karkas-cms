import { Link } from 'react-router-dom';
import { AdminFooter } from '../components/AdminFooter';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

const NAV = [
  { id: 'main', label: 'Основные' },
  { id: 'seo', label: 'SEO' },
  { id: 'mail', label: 'Почта' },
  { id: 'delivery', label: 'Доставка' },
];

export function SettingsPage(): React.JSX.Element {
  const { zipEnabled, maintenance, sites } = useAdmin();
  const defaultSite = sites.find((s) => s.is_default) ?? sites[0];

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Настройки</h1>
          <p>
            {defaultSite !== undefined
              ? `Рабочее пространство: ${defaultSite.label}`
              : 'Параметры сайта и платформы'}
          </p>
        </div>
      </div>
      <MotionSettingsLayout
        zipEnabled={zipEnabled}
        maintenance={maintenance}
        defaultSite={defaultSite}
        nav={NAV}
      />
      <AdminFooter />
    </>
  );
}

function MotionSettingsLayout({
  zipEnabled,
  maintenance,
  defaultSite,
  nav,
}: {
  zipEnabled: boolean;
  maintenance: boolean;
  defaultSite: { label: string; site_key: string; canonical_host: string | null } | undefined;
  nav: Array<{ id: string; label: string }>;
}): React.JSX.Element {
  return (
    <div className="settings-layout" data-testid="settings-panel">
      <nav className="settings-nav card" aria-label="Разделы настроек">
        {nav.map((item, index) => (
          <span key={item.id} className={index === 0 ? 'active' : 'muted'} style={{ display: 'block', padding: '12px 16px' }}>
            {item.label}
            {index > 0 ? (
              <span className="small muted" style={{ display: 'block' }}>
                нет API · этап 8
              </span>
            ) : null}
          </span>
        ))}
      </nav>
      <div className="stack">
        <section className="card form-card">
          <h2 className="mb24">Основные</h2>
          <div className="field">
            <label>
              Название сайта
              <input readOnly value={defaultSite?.label ?? ''} />
            </label>
            <small>Из multisite API (только чтение в 7.5).</small>
          </div>
          <div className="field">
            <label>
              Публичный адрес
              <input readOnly value={defaultSite?.canonical_host ?? '—'} />
            </label>
          </div>
          <div className="kv">
            <span>Шаблоны URL записей</span>
            <code>{'/blog/{slug}'}</code>
          </div>
        </section>
        <section className="card form-card">
          <h2 className="mb16">Платформа</h2>
          <div className="kv">
            <span>ZIP upload</span>
            <strong>{zipEnabled ? 'включён' : 'выключен'}</strong>
          </div>
          <div className="kv">
            <span>Режим обслуживания</span>
            <strong>{maintenance ? 'активен' : 'выключен'}</strong>
          </div>
          <p className="small muted mt24">
            <Link to="/core-update">Обновление Core и версии пакетов</Link>
          </p>
        </section>
        <div className="notice">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>Секреты, SMTP и доставка писем — в макете; API появится на этапе 8. Поля показаны read-only без выдуманных значений.</div>
        </div>
      </div>
    </div>
  );
}
