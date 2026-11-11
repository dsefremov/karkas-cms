import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';

type AdminTopBarProps = {
  pageTitle: string;
};

export function AdminTopBar({ pageTitle }: AdminTopBarProps): React.JSX.Element {
  return (
    <header className="admin-top">
      <div className="row admin-top-crumb">
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
