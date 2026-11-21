import { useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminIcon, NAV_ICONS } from '../AdminIcons';
import type { PageTreeNode } from '../../types/admin';

type PageTreeRowsProps = {
  nodes: PageTreeNode[];
  blogBoundPageId: string | null;
  selectedPageId: string | null;
  onSelect: (node: PageTreeNode) => void;
  depth?: number;
};

/**
 * Дерево страниц по макету tree.html: строка на всю ширину карточки,
 * отступ по уровню вложенности с направляющей, «название + адрес» в одной строке,
 * статус — в отдельной колонке справа.
 */
export function PageTreeRows({
  nodes,
  blogBoundPageId,
  selectedPageId,
  onSelect,
  depth = 0,
}: PageTreeRowsProps): React.JSX.Element {
  return (
    <>
      {nodes.map((node) => (
        <TreeRow
          key={node.id}
          node={node}
          depth={depth}
          blogBoundPageId={blogBoundPageId}
          selectedPageId={selectedPageId}
          onSelect={onSelect}
        />
      ))}
    </>
  );
}

function TreeRow({
  node,
  depth,
  blogBoundPageId,
  selectedPageId,
  onSelect,
}: {
  node: PageTreeNode;
  depth: number;
  blogBoundPageId: string | null;
  selectedPageId: string | null;
  onSelect: (node: PageTreeNode) => void;
}): React.JSX.Element {
  const [expanded, setExpanded] = useState(true);
  const isBlogBound = blogBoundPageId === node.id;
  const isSelected = selectedPageId === node.id;
  const hasChildren = node.children.length > 0;
  const level = Math.min(depth, 6);
  const activityLabel = node.activity === 'active' ? 'Опубликовано' : 'Черновик';

  return (
    <>
      <div
        className={`tree-row-wrap ${isSelected ? 'drop-target' : ''}`}
        style={{ '--tree-level': level } as React.CSSProperties}
        data-level={level}
      >
        <div className="tree-row-name">
          {depth > 0 ? <span className="tree-guide" aria-hidden="true" /> : null}
          <span className="grip" aria-hidden="true">
            ⠿
          </span>
          {hasChildren ? (
            <button
              type="button"
              className="tree-toggle"
              aria-expanded={expanded}
              aria-label={expanded ? `Свернуть «${node.title}»` : `Развернуть «${node.title}»`}
              data-testid={`page-tree-toggle-${node.slug}`}
              onClick={() => setExpanded((value) => !value)}
            >
              <AdminIcon className={`icon ${expanded ? '' : 'collapsed'}`}>{NAV_ICONS.chevronDown}</AdminIcon>
            </button>
          ) : (
            <span className="tree-toggle-spacer" aria-hidden="true" />
          )}
          <button
            type="button"
            className="tree-row-main"
            aria-pressed={isSelected}
            data-testid={`page-tree-node-${node.slug}`}
            onClick={() => onSelect(node)}
          >
            <span className="tree-label">
              <AdminIcon>{isBlogBound ? NAV_ICONS.modules : NAV_ICONS.pageDoc}</AdminIcon>
              <span className={`tree-title ${isBlogBound ? 'strong' : ''}`}>{node.title}</span>
            </span>
            {isBlogBound ? <span className="badge green">Модуль: Блог</span> : null}
            <code className="tree-path">/{node.full_path}</code>
          </button>
          {isBlogBound ? (
            <Link className="small tree-binding-link" to="/modules/blog/binding">
              Привязка
            </Link>
          ) : null}
        </div>
        <span className="tree-status">
          <span className={`badge ${node.activity === 'active' ? 'green' : ''}`}>{activityLabel}</span>
        </span>
      </div>
      {hasChildren && expanded ? (
        <PageTreeRows
          nodes={node.children}
          blogBoundPageId={blogBoundPageId}
          selectedPageId={selectedPageId}
          onSelect={onSelect}
          depth={depth + 1}
        />
      ) : null}
    </>
  );
}
