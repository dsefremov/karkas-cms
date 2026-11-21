import { useMemo } from 'react';
import { AdminIcon, NAV_ICONS } from '../AdminIcons';
import { AdminSelect } from '../AdminSelect';
import { parentSelectOptions, siblingPositionOptions } from '../../lib/pageTree';
import type { PageTreeNode } from '../../types/admin';
type TreeMoveModalProps = {
  open: boolean;
  pageTree: PageTreeNode[];
  selectedPageId: string | null;
  pageTitle: string;
  moveParentId: string;
  setMoveParentId: (id: string) => void;
  moveSortOrder: number;
  setMoveSortOrder: (value: number) => void;
  movePathPreview: { from: string; to: string } | null;
  onClose: () => void;
  onPreview: () => void;
  onConfirm: () => void;
};

export function TreeMoveModal({
  open,
  pageTree,
  selectedPageId,
  pageTitle,
  moveParentId,
  setMoveParentId,
  moveSortOrder,
  setMoveSortOrder,
  movePathPreview,
  onClose,
  onPreview,
  onConfirm,
}: TreeMoveModalProps): React.JSX.Element | null {
  const parentOptions = useMemo(() => parentSelectOptions(pageTree), [pageTree]);
  const positionOptions = useMemo(
    () => siblingPositionOptions(pageTree, moveParentId, selectedPageId),
    [pageTree, moveParentId, selectedPageId],
  );
  if (!open || selectedPageId === null) {
    return null;
  }

  return (
    <div className="modal-backdrop" data-testid="page-move-modal">
      <section className="modal" role="dialog" aria-label="Переместить страницу">
        <div className="overline accent mb16">Альтернатива drag &amp; drop</div>
        <h2>Переместить «{pageTitle}»</h2>
        <p className="mb24">Выберите родителя и положение страницы.</p>
        <div className="field">
          <label htmlFor="move-parent-select">Новый родитель</label>
          <AdminSelect
            id="move-parent-select"
            testId="move-parent-select"
            value={moveParentId}
            onChange={(value) => {
              setMoveParentId(value);
              setMoveSortOrder(0);
            }}
            options={parentOptions.map((option) => ({ value: option.id, label: option.label }))}
          />
        </div>
        <div className="field">
          <label htmlFor="move-position-select">Положение</label>
          <AdminSelect
            id="move-position-select"
            testId="move-position-select"
            value={moveSortOrder}
            onChange={(value) => setMoveSortOrder(Number(value))}
            options={positionOptions.map((option) => ({
              value: option.sortOrder,
              label: option.label,
            }))}
          />
        </div>
        <div className="notice">
          <AdminIcon>{NAV_ICONS.shield}</AdminIcon>
          <div>
            Перемещение меняет полный адрес
            {movePathPreview !== null ? (
              <>
                : <code>{movePathPreview.from}</code> → <code>{movePathPreview.to}</code>
              </>
            ) : (
              '. Нажмите «Предпросмотр», чтобы увидеть новый путь.'
            )}
            . Если адрес был публичным, со старого пути создаётся 301 (GET/HEAD).
          </div>
        </div>
        <div className="row between mt24 gap8 wrap">
          <button type="button" className="btn" onClick={onClose}>
            Отмена
          </button>
          <MotionPreviewConfirm onPreview={onPreview} onConfirm={onConfirm} />
        </div>
        <p className="small mt24">
          При конфликте версии: «Структура обновилась. Обновите дерево и повторите перемещение».
        </p>
      </section>
    </div>
  );
}

function MotionPreviewConfirm({
  onPreview,
  onConfirm,
}: {
  onPreview: () => void;
  onConfirm: () => void;
}): React.JSX.Element {
  return (
    <div className="row gap8">
      <button type="button" className="btn" data-testid="page-move-preview" onClick={() => void onPreview()}>
        Предпросмотр
      </button>
      <button type="button" className="btn primary" data-testid="page-move-confirm" onClick={() => void onConfirm()}>
        Переместить
      </button>
    </div>
  );
}
