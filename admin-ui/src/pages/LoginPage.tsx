import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { AdminBanners } from '../components/AdminBanners';
import { useAdmin } from '../context/AdminContext';

export function LoginPage(): React.JSX.Element {
  const { password, setPassword, authenticated, login, error } = useAdmin();
  const navigate = useNavigate();

  useEffect(() => {
    if (authenticated) {
      navigate('/dashboard', { replace: true });
    }
  }, [authenticated, navigate]);

  return (
    <div className="admin" data-testid="admin-shell" role="document">
      <header className="admin-top" style={{ margin: 0 }}>
        <span className="brand">Каркас CMS</span>
        <span className="small muted">Служебный вход</span>
      </header>
      <main style={{ maxWidth: 1120, margin: '48px auto', padding: '0 24px' }}>
        <div style={{ maxWidth: 600, margin: '0 auto' }}>
          <div className="page-head">
            <div>
              <h1>Вход в админку</h1>
              <p>Каркас · служебный аккаунт</p>
            </div>
          </div>
          <AdminBanners />
          <section className="card" aria-labelledby="auth-heading">
            <h2 id="auth-heading">Вход администратора</h2>
            {!authenticated ? (
              <div className="form-row">
                <label htmlFor="admin-password">Пароль</label>
                <input
                  id="admin-password"
                  data-testid="admin-password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
                <button type="button" className="btn primary" data-testid="admin-login" onClick={() => void login()}>
                  Войти
                </button>
              </div>
            ) : (
              <p data-testid="admin-authenticated">Сессия активна (повторная аутентификация для установки через заголовок).</p>
            )}
            {error !== null && authenticated === false && (
              <p className="small muted" role="alert">
                {error}
              </p>
            )}
          </section>
        </div>
      </main>
    </div>
  );
}
