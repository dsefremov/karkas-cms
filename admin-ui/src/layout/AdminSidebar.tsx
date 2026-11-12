import { NavLink } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

function navClassName({ isActive }: { isActive: boolean }): string {
  return isActive ? 'nav-item active' : 'nav-item';
}

type AdminSidebarProps = {
  postsCount: number | null;
  operationsCount: number | null;
};

export function AdminSidebar({ postsCount, operationsCount }: AdminSidebarProps): React.JSX.Element {
  const { sites } = useAdmin();
  const defaultSite = sites.find((s) => s.is_default) ?? sites[0];

  return (
    <aside className="sidebar" aria-label="Навигация">
      <NavLink to="/dashboard" className="brand">
        <span className="brand-mark">/</span>
        Каркас{' '}
        <span className="small muted" style={{ fontWeight: 400, letterSpacing: 0 }}>
          CMS
        </span>
      </NavLink>
      {defaultSite !== undefined && (
        <div className="workspace">
          <div>
            <strong style={{ fontSize: 12 }}>{defaultSite.label || defaultSite.site_key}</strong>
            <div className="small muted">Сайт: {defaultSite.site_key}</div>
          </div>
        </div>
      )}
      <div className="nav-label">Рабочее пространство</div>
      <nav>
        <NavLink to="/dashboard" className={navClassName} data-testid="nav-dashboard">
          <AdminIcon>{NAV_ICONS.dashboard}</AdminIcon>
          Обзор
        </NavLink>
        <NavLink to="/posts" className={navClassName} data-testid="nav-posts">
          <AdminIcon>{NAV_ICONS.posts}</AdminIcon>
          Записи
          {postsCount !== null && postsCount > 0 ? <span className="count">{postsCount}</span> : null}
        </NavLink>
        <NavLink to="/categories" className={navClassName} data-testid="nav-categories">
          <AdminIcon>{NAV_ICONS.categories}</AdminIcon>
          Категории
        </NavLink>
        <NavLink to="/pages" className={navClassName} data-testid="nav-pages">
          <AdminIcon>{NAV_ICONS.pages}</AdminIcon>
          Страницы
        </NavLink>
        <NavLink to="/users" className={navClassName} data-testid="nav-users">
          <AdminIcon>{NAV_ICONS.users}</AdminIcon>
          Пользователи
        </NavLink>
        <NavLink to="/modules" className={navClassName} data-testid="nav-modules">
          <AdminIcon>{NAV_ICONS.modules}</AdminIcon>
          Модули
        </NavLink>
        <NavLink to="/sites" className={navClassName} data-testid="nav-sites">
          <AdminIcon>{NAV_ICONS.sites}</AdminIcon>
          Сайты и шаблоны
        </NavLink>
        <NavLink to="/settings" className={navClassName} data-testid="nav-settings">
          <AdminIcon>{NAV_ICONS.settings}</AdminIcon>
          Настройки
        </NavLink>
        <div className="nav-label">Система</div>
        <NavLink to="/operations" className={navClassName} data-testid="nav-operations">
          <AdminIcon>{NAV_ICONS.operations}</AdminIcon>
          Операции
          {operationsCount !== null && operationsCount > 0 ? (
            <span className="badge amber">{operationsCount}</span>
          ) : null}
        </NavLink>
        <NavLink to="/roles" className={navClassName} data-testid="nav-roles">
          <AdminIcon>{NAV_ICONS.roles}</AdminIcon>
          Роли и доступ
        </NavLink>
      </nav>
      <div className="sidebar-bottom">
        <a className="nav-item" href="/" target="_blank" rel="noreferrer" data-testid="nav-open-site">
          <AdminIcon>{NAV_ICONS.external}</AdminIcon>
          Открыть сайт
        </a>
        <div className="sidebar-user row">
          <span className="avatar">СА</span>
          <div>
            <strong style={{ fontSize: 12 }}>Служебный админ</strong>
            <div className="small muted">Установка / CMS</div>
          </div>
        </div>
      </div>
    </aside>
  );
}
