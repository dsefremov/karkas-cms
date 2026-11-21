import { useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { AdminIcon, NAV_ICONS } from './AdminIcons';

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
  placeholder?: string;
};

type PopupRect = { left: number; top: number; width: number; maxHeight: number; above: boolean };

/**
 * Выпадающий список по макету v16 (кнопка-поле с шевроном + всплывающий список).
 * Паттерн WAI-ARIA «select-only combobox»: button[role=combobox] + ul[role=listbox].
 * Попап позиционируется fixed относительно кнопки, поэтому не обрезается таблицами и модалками.
 */
export function AdminSelect({
  id,
  testId,
  'aria-label': ariaLabel,
  value,
  onChange,
  options,
  disabled = false,
  className = '',
  placeholder = '—',
}: AdminSelectProps): React.JSX.Element {
  const listboxId = useId();
  const triggerRef = useRef<HTMLButtonElement>(null);
  const listRef = useRef<HTMLUListElement>(null);
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(-1);
  const [rect, setRect] = useState<PopupRect | null>(null);

  const selectedIndex = options.findIndex((option) => String(option.value) === String(value));
  const selected = selectedIndex >= 0 ? options[selectedIndex] : undefined;

  const measure = useCallback(() => {
    const trigger = triggerRef.current;
    if (trigger === null) {
      return;
    }
    const box = trigger.getBoundingClientRect();
    const spaceBelow = window.innerHeight - box.bottom - 8;
    const spaceAbove = box.top - 8;
    const above = spaceBelow < 160 && spaceAbove > spaceBelow;
    const maxHeight = Math.max(120, Math.min(320, above ? spaceAbove : spaceBelow));
    setRect({
      left: box.left,
      top: above ? box.top - 4 : box.bottom + 4,
      width: box.width,
      maxHeight,
      above,
    });
  }, []);

  const openList = useCallback(
    (index?: number) => {
      if (disabled || options.length === 0) {
        return;
      }
      measure();
      setActiveIndex(index ?? (selectedIndex >= 0 ? selectedIndex : 0));
      setOpen(true);
    },
    [disabled, options.length, measure, selectedIndex],
  );

  const close = useCallback((focusTrigger: boolean) => {
    setOpen(false);
    if (focusTrigger) {
      triggerRef.current?.focus();
    }
  }, []);

  const commit = useCallback(
    (index: number) => {
      const option = options[index];
      if (option !== undefined) {
        onChange(String(option.value));
      }
      close(true);
    },
    [options, onChange, close],
  );

  useLayoutEffect(() => {
    if (!open) {
      return;
    }
    const onWindowChange = (): void => measure();
    window.addEventListener('resize', onWindowChange);
    window.addEventListener('scroll', onWindowChange, true);
    return () => {
      window.removeEventListener('resize', onWindowChange);
      window.removeEventListener('scroll', onWindowChange, true);
    };
  }, [open, measure]);

  useEffect(() => {
    if (!open) {
      return;
    }
    const onPointerDown = (event: PointerEvent): void => {
      const target = event.target as Node;
      if (triggerRef.current?.contains(target) || listRef.current?.contains(target)) {
        return;
      }
      close(false);
    };
    document.addEventListener('pointerdown', onPointerDown);
    return () => document.removeEventListener('pointerdown', onPointerDown);
  }, [open, close]);

  useEffect(() => {
    if (!open || activeIndex < 0) {
      return;
    }
    const node = listRef.current?.children.item(activeIndex);
    if (node instanceof HTMLElement) {
      node.scrollIntoView({ block: 'nearest' });
    }
  }, [open, activeIndex]);

  function onKeyDown(event: React.KeyboardEvent<HTMLButtonElement>): void {
    if (disabled) {
      return;
    }
    const last = options.length - 1;
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        if (!open) {
          openList();
        } else {
          setActiveIndex((i) => Math.min(last, i + 1));
        }
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (!open) {
          openList();
        } else {
          setActiveIndex((i) => Math.max(0, i - 1));
        }
        break;
      case 'Home':
        if (open) {
          event.preventDefault();
          setActiveIndex(0);
        }
        break;
      case 'End':
        if (open) {
          event.preventDefault();
          setActiveIndex(last);
        }
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        if (open && activeIndex >= 0) {
          commit(activeIndex);
        } else {
          openList();
        }
        break;
      case 'Escape':
        if (open) {
          event.preventDefault();
          close(true);
        }
        break;
      case 'Tab':
        if (open) {
          close(false);
        }
        break;
      default:
        break;
    }
  }

  return (
    <div className={`admin-select ${open ? 'open' : ''} ${className}`.trim()}>
      <button
        ref={triggerRef}
        id={id}
        type="button"
        role="combobox"
        className="admin-select-trigger"
        data-testid={testId}
        data-value={selected !== undefined ? String(selected.value) : ''}
        aria-label={ariaLabel}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls={listboxId}
        aria-activedescendant={open && activeIndex >= 0 ? `${listboxId}-opt-${activeIndex}` : undefined}
        disabled={disabled}
        onClick={() => (open ? close(false) : openList())}
        onKeyDown={onKeyDown}
      >
        <span className="admin-select-value">{selected?.label ?? placeholder}</span>
        <AdminIcon className="icon admin-select-chevron">{NAV_ICONS.chevronDown}</AdminIcon>
      </button>
      <ul
        ref={listRef}
        id={listboxId}
        role="listbox"
        tabIndex={-1}
        aria-labelledby={id}
        className="admin-select-list"
        data-testid={testId !== undefined ? `${testId}-listbox` : undefined}
        hidden={!open}
        style={
          rect !== null
            ? {
                left: rect.left,
                top: rect.above ? undefined : rect.top,
                bottom: rect.above ? window.innerHeight - rect.top : undefined,
                minWidth: rect.width,
                maxHeight: rect.maxHeight,
              }
            : undefined
        }
      >
        {options.map((option, index) => {
          const isSelected = index === selectedIndex;
          return (
            <li
              key={`${option.value}-${option.label}`}
              id={`${listboxId}-opt-${index}`}
              role="option"
              aria-selected={isSelected}
              data-value={String(option.value)}
              className={`admin-select-option ${index === activeIndex ? 'active' : ''} ${isSelected ? 'selected' : ''}`.trim()}
              onPointerMove={() => setActiveIndex(index)}
              onClick={() => commit(index)}
            >
              {option.label}
            </li>
          );
        })}
      </ul>
    </div>
  );
}
