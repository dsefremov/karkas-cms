type ReauthModalProps = {
  open: boolean;
  operation: string;
  password: string;
  error: string | null;
  onPasswordChange: (value: string) => void;
  onCancel: () => void;
  onConfirm: () => void;
};

export function ReauthModal({
  open,
  operation,
  password,
  error,
  onPasswordChange,
  onCancel,
  onConfirm,
}: ReauthModalProps): React.JSX.Element | null {
  if (!open) {
    return null;
  }

  return (
    <div className="modal-backdrop" data-testid="reauth-modal-backdrop">
      <section className="modal reauth-modal" role="dialog" aria-label="Повторное подтверждение">
        <div className="overline accent mb16">Каркас · опасное действие</div>
        <h2 className="mb16">Подтвердите личность</h2>
        <p className="small muted mb16" data-testid="reauth-operation">
          Операция: {operation}. Пароль дополнительно к основной сессии; ошибка не запускает действие.
        </p>
        <div className="field">
          <label htmlFor="reauth-password">
            Пароль администратора
            <input
              id="reauth-password"
              data-testid="reauth-password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => onPasswordChange(e.target.value)}
            />
          </label>
        </div>
        {error !== null && (
          <p className="banner error" data-testid="reauth-error">
            {error}
          </p>
        )}
        <div className="row between mt24">
          <button type="button" className="btn" data-testid="reauth-cancel" onClick={onCancel}>
            Отмена
          </button>
          <button type="button" className="btn primary" data-testid="reauth-confirm" onClick={onConfirm}>
            Подтвердить
          </button>
        </div>
      </section>
    </div>
  );
}
