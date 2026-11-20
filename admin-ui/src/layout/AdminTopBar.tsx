import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';

type AdminTopBarProps = {
  pageTitle: string;
  onMenuToggle?: () => void;
};

export function AdminTopBar({ pageTitle, onMenuToggle }: AdminTopBarProps): React.JSX.Element {
  return (
    <header className="admin-top">
      <div className="row admin-top-crumb">
        <button
          type="button"
          className="btn quiet admin-menu-toggle"
          aria-label="Меню"
          onClick={() => onMenuToggle?.()}
        >
          <AdminIcon>{NAV_ICONS.menu}</AdminIcon>
        </button>
        <span className="muted">Каркас · Рабочее пространство</span>
        <AdminIcon>{NAV_ICONS.chevron}</AdminIcon>
        <strong>{pageTitle}</strong>
      </div>
      <div className="row admin-top-tools">
        <span className="muted">RU</span>
        <button type="button" className="btn quiet admin-icon-btn" aria-label="Уведомления">
          <AdminIcon>{NAV_ICONS.bell}</AdminIcon>
        </button>
        <span className="avatar">СА</span>
      </div>
    </header>
  );
}
