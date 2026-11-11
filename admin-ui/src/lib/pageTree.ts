import { PAGE_TREE_ROOT, type PageTreeNode } from '../types/admin';

export function flattenPageTree(nodes: PageTreeNode[], acc: PageTreeNode[] = []): PageTreeNode[] {
  for (const node of nodes) {
    acc.push(node);
    flattenPageTree(node.children, acc);
  }
  return acc;
}

export function findPageNode(nodes: PageTreeNode[], id: string): PageTreeNode | undefined {
  for (const node of nodes) {
    if (node.id === id) {
      return node;
    }
    const nested = findPageNode(node.children, id);
    if (nested !== undefined) {
      return nested;
    }
  }
  return undefined;
}

export function pageDepth(nodes: PageTreeNode[], targetId: string, depth = 0): number {
  for (const node of nodes) {
    if (node.id === targetId) {
      return depth;
    }
    const childDepth = pageDepth(node.children, targetId, depth + 1);
    if (childDepth >= 0) {
      return childDepth;
    }
  }
  return -1;
}

export type ParentOption = {
  id: string;
  label: string;
};

export function parentSelectOptions(nodes: PageTreeNode[]): ParentOption[] {
  const options: ParentOption[] = [{ id: PAGE_TREE_ROOT, label: 'Корень сайта' }];
  const walk = (list: PageTreeNode[], prefix: string): void => {
    for (const node of list) {
      const path = prefix === '' ? node.title : `${prefix} → ${node.title}`;
      options.push({ id: node.id, label: path });
      walk(node.children, path);
    }
  };
  walk(nodes, '');
  return options;
}

export function siblingPositionOptions(
  nodes: PageTreeNode[],
  parentId: string,
  excludeId: string | null,
): Array<{ sortOrder: number; label: string }> {
  const flat = flattenPageTree(nodes);
  const siblings = flat
    .filter((n) => n.parent_id === parentId && n.id !== excludeId)
    .sort((a, b) => a.sort_order - b.sort_order);
  const options: Array<{ sortOrder: number; label: string }> = [
    { sortOrder: 0, label: 'Первой внутри раздела' },
  ];
  siblings.forEach((sibling, index) => {
    options.push({
      sortOrder: sibling.sort_order + 1,
      label: `После «${sibling.title}»`,
    });
    if (index === siblings.length - 1) {
      options[options.length - 1].sortOrder = sibling.sort_order + 1;
    }
  });
  return options;
}
