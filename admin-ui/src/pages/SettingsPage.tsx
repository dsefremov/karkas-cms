import { Link } from 'react-router-dom';
import { useAdmin } from '../context/AdminContext';

export function SettingsPage(): React.JSX.Element {
  const { zipEnabled, maintenance } = useAdmin();

  return (
    <>
      <div className="page-head">
        <div>
          <h1>Настройки</h1>
          <p>Загрузка пакетов и режим обслуживания.</p>
        </div>
      </div>
      <section className="card">
        <h2>Платформа</h2>
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
    </>
  );
}
