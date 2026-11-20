type AdminSelectOption = {
  value: string | number;
  label: string;
};

type AdminSelectProps = {
  id: string;
  testId?: string;
  'aria-label'?: string;
  value: string | number;
  onChange: (value: string) => void;
  options: AdminSelectOption[];
  disabled?: boolean;
  className?: string;
};

export function AdminSelect({
  id,
  testId,
  'aria-label': ariaLabel,
  value,
  onChange,
  options,
  disabled = false,
  className = '',
}: AdminSelectProps): React.JSX.Element {
  return (
    <div className={`admin-select ${className}`.trim()}>
      <select
        id={id}
        data-testid={testId}
        aria-label={ariaLabel}
        value={String(value)}
        disabled={disabled}
        onChange={(e) => onChange(e.target.value)}
      >
        {options.map((option) => (
          <option key={`${option.value}-${option.label}`} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}