import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { AdminBanners } from '../components/AdminBanners';
import { AdminIcon, NAV_ICONS } from '../components/AdminIcons';
import { useAdmin } from '../context/AdminContext';

export function LoginPage(): React.JSX.Element {
  const { password, setPassword, authenticated, sessionReady, login, error } = useAdmin();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (!sessionReady || !authenticated) {
      return;
    }
    const from =
      (location.state as { from?: { pathname?: string } } | null)?.from?.pathname ?? '/dashboard';
    navigate(from, { replace: true });
  }, [sessionReady, authenticated, location.state, navigate]);

  return (
    <div className="admin" data-testid="admin-shell" role="document">
      <header className="admin-top" style={{ margin: 0 }}>
        <span className="brand">
          <AdminIcon>{NAV_ICONS.dashboard}</AdminIcon> Каркас <span className="small muted">Karkas CMS</span>
        </span>
        <span className="small muted">Служебный вход · отдельный контур</span>
      </header>
      <main style={{ maxWidth: 1120, margin: '48px auto', padding: '0 24px' }}>
        <div style={{ maxWidth: 600, margin: '0 auto' }}>
          <div className="page-head">
            <div>
              <h1>Вход в админку</h1>
              <p>Каркас · служебный аккаунт, не кабинет Автора</p>
            </div>
          </div>
          <AdminBanners />
          <section className="card" aria-labelledby="auth-heading">
            {!authenticated ? (
              <>
                <div className="field">
                  <label htmlFor="admin-password">
                    Пароль служебной сессии
                    <input
                      id="admin-password"
                      data-testid="admin-password"
                      type="password"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      autoComplete="current-password"
                    />
                  </label>
                </div>
                <div className="row gap8 wrap mt24">
                  <button
                    type="button"
                    className="btn primary"
                    data-testid="admin-login"
                    onClick={() => void login()}
                  >
                    Продолжить
                  </button>
                </div>
              </>
            ) : (
              <p className="small muted">Сессия активна. Перенаправление…</p>
            )}
            {error !== null && authenticated === false ? (
              <p className="field-error" role="alert">
                {error}
              </p>
            ) : null}
          </section>
          <p className="small muted mt24">Каркас · этап 7.5 · двухфакторная проверка — этап 8</p>
        </div>
      </main>
    </div>
  );
}
