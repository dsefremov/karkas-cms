import type { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

export function AdminIcon({ children, ...props }: IconProps & { children: React.ReactNode }): React.JSX.Element {
  return (
    <svg className="icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true" {...props}>
      {children}
    </svg>
  );
}

export const NAV_ICONS = {
  dashboard: <path d="M3 3h5v5H3zm9 0h5v5h-5zM3 12h5v5H3zm9 0h5v5h-5z" strokeLinecap="round" strokeLinejoin="round" />,
  posts: <path d="M5 2h8l4 4v12H5zM12 2v5h5M8 11h6M8 14h6" strokeLinecap="round" strokeLinejoin="round" />,
  categories: <path d="M2 5h6l2 2h8v10H2z" strokeLinecap="round" strokeLinejoin="round" />,
  pages: <path d="M10 3v5M4 12V8h12v4M2 13h4v4H2zm6-12h4v4H8zm6 12h4v4h-4z" strokeLinecap="round" strokeLinejoin="round" />,
  users: (
    <path
      d="M7 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6M1 18v-2a6 6 0 0 1 12 0v2M14 4a3 3 0 0 1 0 6m1 3a5 5 0 0 1 4 5"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  modules: <path d="M3 3h5v5H3zm9 0h5v5h-5zM3 12h5v5H3zm9 0h5v5h-5z" strokeLinecap="round" strokeLinejoin="round" />,
  sites: <path d="M2 3h16v11H2zM6 18h8M10 14v4" strokeLinecap="round" strokeLinejoin="round" />,
  settings: (
    <path
      d="M10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6M8 2h4l1 3 3 1 2 3-2 3-1 3-3 1-1 2H8l-1-3-3-1-2-3 2-3 1-3 3-1z"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  operations: <path d="M10 5v5l3 2M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0" strokeLinecap="round" strokeLinejoin="round" />,
  roles: (
    <path d="m10 2 7 3v5c0 5-7 8-7 8s-7-3-7-8V5zM6 10l3 3 5-6" strokeLinecap="round" strokeLinejoin="round" />
  ),
  external: <path d="M11 3h6v6m0-6L8 12M7 3H3v14h14v-4" strokeLinecap="round" strokeLinejoin="round" />,
  chevron: <path d="m7 4 6 6-6 6" strokeLinecap="round" strokeLinejoin="round" />,
  bell: <path d="M4 14V8a6 6 0 0 1 12 0v6l2 2H2zm4 4h4" strokeLinecap="round" strokeLinejoin="round" />,
  plus: <path d="M10 3v14M3 10h14" strokeLinecap="round" strokeLinejoin="round" />,
  pageDoc: <path d="M5 2h8l4 4v12H5zM12 2v5h5M8 11h6M8 14h6" strokeLinecap="round" strokeLinejoin="round" />,
  chevronDown: <path d="m5 8 5 5 5-5" strokeLinecap="round" strokeLinejoin="round" />,
  shield: (
    <path d="m10 2 7 3v5c0 5-7 8-7 8s-7-3-7-8V5zM6 10l3 3 5-6" strokeLinecap="round" strokeLinejoin="round" />
  ),
} as const;
