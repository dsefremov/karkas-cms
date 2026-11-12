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

export function PageTreeRows({
  nodes,
  blogBoundPageId,
  selectedPageId,
  onSelect,
  depth = 0,
}: PageTreeRowsProps): React.JSX.Element {
  return (
    <>
      {nodes.map((node) => {
        const levelClass = `level${Math.min(depth, 3)}`;
        const isBlogBound = blogBoundPageId === node.id;
        const isSelected = selectedPageId === node.id;
        return (
          <TreeRowFragment
            key={node.id}
            node={node}
            levelClass={levelClass}
            isBlogBound={isBlogBound}
            isSelected={isSelected}
            onSelect={onSelect}
            blogBoundPageId={blogBoundPageId}
            selectedPageId={selectedPageId}
            depth={depth}
          />
        );
      })}
    </>
  );
}

function TreeRowFragment(props: {
  node: PageTreeNode;
  levelClass: string;
  isBlogBound: boolean;
  isSelected: boolean;
  onSelect: (node: PageTreeNode) => void;
  blogBoundPageId: string | null;
  selectedPageId: string | null;
  depth: number;
}): React.JSX.Element {
  const { node, levelClass, isBlogBound, isSelected, onSelect, blogBoundPageId, selectedPageId, depth } =
    props;
  const activityLabel = node.activity === 'active' ? 'Опубликовано' : 'Черновик';

  return (
    <>
      <button
        type="button"
        className={`tree-row ${levelClass} ${isSelected ? 'drop-target' : ''}`}
        data-testid={`page-tree-node-${node.slug}`}
        onClick={() => onSelect(node)}
      >
        <span className="grip" aria-hidden="true">
          ⠿
        </span>
        <AdminIcon>{isBlogBound ? NAV_ICONS.modules : NAV_ICONS.pageDoc}</AdminIcon>
        {isBlogBound ? (
          <Link to="/modules/blog/binding" onClick={(e) => e.stopPropagation()}>
            <strong>{node.title}</strong>
          </Link>
        ) : (
          <span>{node.title}</span>
        )}
        {isBlogBound ? <span className="badge green">Модуль: Блог</span> : null}
        <code className="small muted">/{node.full_path}</code>
        <span className="tree-status">
          <span className={`badge ${node.activity === 'active' ? 'green' : ''}`}>{activityLabel}</span>
        </span>
      </button>
      {node.children.length > 0 ? (
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
