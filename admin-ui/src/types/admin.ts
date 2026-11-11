export const PAGE_TREE_ROOT = '00000000-0000-0000-0000-000000000001';

export type PackageVersionRow = {
  package_id: string;
  current: string | null;
  available: string | null;
  status: string;
};

export type VersionsPayload = {
  packages: PackageVersionRow[];
  stage: number;
};

export type BlogVersionRow = {
  version_seq: number;
  saved_at: string;
  title: string;
  activity: string;
};

export type SiteRow = {
  id: number;
  site_key: string;
  label: string;
  is_default: boolean;
  canonical_host: string | null;
  blog_enabled: boolean;
};

export type PageTreeNode = {
  id: string;
  parent_id: string;
  slug: string;
  sort_order: number;
  title: string;
  activity: string;
  full_path: string;
  children: PageTreeNode[];
};

export type CorePlanPayload = {
  compatible: boolean;
  errors: string[];
  plan: {
    core_version?: string;
    targets?: Array<{ package_id: string; version: string }>;
  };
};
