import { useAdmin } from '../context/AdminContext';

export function VersionsPanel(): React.JSX.Element {
  const { versions } = useAdmin();

  return (
    <section className="card" data-testid="versions-panel">
      <h2>Версии</h2>
      <div className="table-scroll">
        <table className="versions-table">
          <thead>
            <tr>
              <th scope="col">Пакет</th>
              <th scope="col">Текущая</th>
              <th scope="col">Доступная (локально)</th>
            </tr>
          </thead>
          <tbody>
            {versions?.packages.map((row) => (
              <tr key={row.package_id} data-testid={`version-row-${row.package_id.replace('/', '-')}`}>
                <td>{row.package_id}</td>
                <td>{row.current ?? '—'}</td>
                <td>{row.available ?? '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}
