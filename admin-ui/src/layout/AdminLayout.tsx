import { useEffect, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { AdminBanners } from '../components/AdminBanners';
import { useAdmin } from '../context/AdminContext';
import { useOperationsCount } from '../hooks/useOperationsCount';
import { ROUTE_TITLES } from './routeTitles';
import { AdminSidebar } from './AdminSidebar';
import { AdminTopBar } from './AdminTopBar';

export function AdminLayout(): React.JSX.Element {
  const { authenticated, sessionReady } = useAdmin();
  const navigate = useNavigate();
  const location = useLocation();
  const operationsCount = useOperationsCount(authenticated);
  const [postsCount, setPostsCount] = useState<number | null>(null);

  const segment = location.pathname.replace(/^\//, '').split('/')[0] ?? 'dashboard';
  const pageTitle = ROUTE_TITLES[segment] ?? 'Админка';

  useEffect(() => {
    if (sessionReady && !authenticated && location.pathname !== '/login') {
      navigate('/login', { replace: true, state: { from: location } });
    }
  }, [sessionReady, authenticated, location, navigate]);

  useEffect(() => {
    if (!authenticated) {
      setPostsCount(null);
      return;
    }
    void (async () => {
      const response = await fetch('/api/v1/blog/posts');
      if (!response.ok) {
        setPostsCount(null);
        return;
      }
      const body = (await response.json()) as { posts?: unknown[] };
      setPostsCount(body.posts?.length ?? 0);
    })();
  }, [authenticated, location.pathname]);

  if (!sessionReady || !authenticated) {
    return <></>;
  }

  return (
    <div className="admin" data-testid="admin-shell" role="document">
      <span data-testid="admin-authenticated" hidden aria-hidden="true">
        session
      </span>
      <AdminSidebar postsCount={postsCount} operationsCount={operationsCount} />
      <div className="admin-body">
        <AdminTopBar pageTitle={pageTitle} />
        <main className="admin-main">
          <AdminBanners />
          <Outlet />
        </main>
      </div>
    </div>
  );
}
