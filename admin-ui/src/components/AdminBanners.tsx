import { useAdmin } from '../context/AdminContext';

export function AdminBanners(): React.JSX.Element {
  const { maintenance, error, message } = useAdmin();

  return (
    <>
      {maintenance && (
        <div className="banner-warn" data-testid="maintenance-banner" role="status">
          Режим обслуживания активен — записи API заморожены.
        </div>
      )}
      {error !== null && (
        <p className="status-banner" role="alert" data-testid="admin-error">
          {error}
        </p>
      )}
      {message !== null && (
        <p className="status-banner" role="status" data-testid="admin-message">
          {message}
        </p>
      )}
    </>
  );
}
