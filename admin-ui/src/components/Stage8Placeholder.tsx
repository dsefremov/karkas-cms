type Stage8PlaceholderProps = {
  title: string;
};

export function Stage8Placeholder({ title }: Stage8PlaceholderProps): React.JSX.Element {
  return (
    <div className="page-head" data-testid="stage8-placeholder">
      <div>
        <h1>{title}</h1>
        <p className="empty-state">появится на этапе 8</p>
      </div>
    </div>
  );
}
