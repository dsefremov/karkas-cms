import { useAdmin } from '../context/AdminContext';

export function AdminFooter(): React.JSX.Element {
  const { coreRow } = useAdmin();
  const coreVersion = coreRow?.current ?? coreRow?.available ?? '—';

  return (
    <div className="footer-note" data-testid="admin-footer-note">
      <span>
        Ядро Каркас {coreVersion} · Каркас CMS · этап 7.5
      </span>
      <span>Время в интерфейсе — МСК</span>
    </div>
  );
}
