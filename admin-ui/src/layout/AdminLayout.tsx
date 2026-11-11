import { useEffect } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { AdminBanners } from '../components/AdminBanners';
import { useAdmin } from '../context/AdminContext';
import { ROUTE_TITLES } from './routeTitles';

function navClassName({ isActive }: { isActive: boolean }): string {
  return isActive ? 'nav-item active' : 'nav-item';
}

export function AdminLayout(): React.JSX.Element {
  const { authenticated, sessionReady } = useAdmin();
  const navigate = useNavigate();
  const location = useLocation();

  const segment = location.pathname.replace(/^\//, '').split('/')[0] ?? 'dashboard';
  const pageTitle = ROUTE_TITLES[segment] ?? 'Админка';

  useEffect(() => {
    if (sessionReady && !authenticated && location.pathname !== '/login') {
      navigate('/login', { replace: true, state: { from: location } });
    }
  }, [sessionReady, authenticated, location, navigate]);

  if (!sessionReady || !authenticated) {
    return <></>;
  }

  return (
    <div className="admin" data-testid="admin-shell" role="document">
      <aside className="sidebar" aria-label="Навигация">
        <div className="brand">
          <span className="brand-mark">/</span>
          Каркас{' '}
          <span className="small muted" style={{ fontWeight: 400, letterSpacing: 0 }}>
            CMS
          </span>
        </div>
        <div className="nav-label">Рабочее пространство</div>
        <nav>
          <NavLink to="/dashboard" className={navClassName} data-testid="nav-dashboard">
            Обзор
          </NavLink>
          <NavLink to="/posts" className={navClassName} data-testid="nav-posts">
            Записи
          </NavLink>
          <NavLink to="/categories" className={navClassName} data-testid="nav-categories">
            Категории
          </NavLink>
          <NavLink to="/pages" className={navClassName} data-testid="nav-pages">
            Страницы
          </NavLink>
          <NavLink to="/users" className={navClassName} data-testid="nav-users">
            Пользователи
          </NavLink>
          <NavLink to="/modules" className={navClassName} data-testid="nav-modules">
            Модули
          </NavLink>
          <NavLink to="/sites" className={navClassName} data-testid="nav-sites">
            Сайты
          </NavLink>
          <NavLink to="/settings" className={navClassName} data-testid="nav-settings">
            Настройки
          </NavLink>
          <div className="nav-label">Система</div>
          <NavLink to="/operations" className={navClassName} data-testid="nav-operations">
            Операции
          </NavLink>
          <NavLink to="/roles" className={navClassName} data-testid="nav-roles">
            Роли и доступ
          </NavLink>
        </nav>
        <div className="sidebar-bottom">
          <a className="nav-item" href="/" target="_blank" rel="noreferrer" data-testid="nav-open-site">
            Открыть сайт
          </a>
        </div>
      </aside>
      <div className="admin-body">
        <header className="admin-top">
          <div className="row">
            <span className="muted">Каркас · Рабочее пространство</span>
            <strong>{pageTitle}</strong>
          </div>
        </header>
        <main className="admin-main">
          <p data-testid="admin-authenticated" className="small muted">
            Сессия активна (повторная аутентификация для установки через заголовок).
          </p>
          <AdminBanners />
          <Outlet />
        </main>
      </div>
    </div>
  );
}
