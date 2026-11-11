import { useCallback, useState } from 'react';
import type { BlogVersionRow } from '../types/admin';

export type BlogPostRow = {
  id: string;
  slug: string;
  title: string;
  activity: string;
  updated_at: string;
};

export type BlogPostDetail = BlogPostRow & {
  body_text: string;
  content_etag: string;
};

export function useBlogPosts(): {
  posts: BlogPostRow[];
  loading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
  loadPost: (id: string) => Promise<BlogPostDetail | null>;
  createPost: (payload: { title: string; slug: string; body_text: string; activity: string }) => Promise<string | null>;
  savePost: (
    id: string,
    payload: { title: string; slug: string; body_text: string; activity: string },
  ) => Promise<boolean>;
  loadHistory: (id: string) => Promise<BlogVersionRow[]>;
  restoreVersion: (id: string, versionSeq: number) => Promise<boolean>;
} {
  const [posts, setPosts] = useState<BlogPostRow[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    const response = await fetch('/api/v1/blog/posts');
    if (!response.ok) {
      const body = (await response.json().catch(() => ({}))) as { error?: string };
      setError(body.error ?? 'Не удалось загрузить записи');
      setPosts([]);
      setLoading(false);
      return;
    }
    const body = (await response.json()) as { posts: BlogPostRow[] };
    setPosts(body.posts);
    setLoading(false);
  }, []);

  const loadPost = useCallback(async (id: string): Promise<BlogPostDetail | null> => {
    const response = await fetch(`/api/v1/blog/posts/${id}`);
    if (!response.ok) {
      return null;
    }
    const body = (await response.json()) as { post: BlogPostDetail };

    return body.post;
  }, []);

  const createPost = useCallback(
    async (payload: { title: string; slug: string; body_text: string; activity: string }): Promise<string | null> => {
      const response = await fetch('/api/v1/blog/posts', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.ok) {
        return null;
      }
      const body = (await response.json()) as { id: string };
      await refresh();

      return body.id;
    },
    [refresh],
  );

  const savePost = useCallback(
    async (
      id: string,
      payload: { title: string; slug: string; body_text: string; activity: string },
    ): Promise<boolean> => {
      const response = await fetch(`/api/v1/blog/posts/${id}/save`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      return response.ok;
    },
    [],
  );

  const loadHistory = useCallback(async (id: string): Promise<BlogVersionRow[]> => {
    const response = await fetch(`/api/v1/blog/posts/${id}/versions`);
    if (!response.ok) {
      return [];
    }
    const body = (await response.json()) as { versions: BlogVersionRow[] };

    return body.versions;
  }, []);

  const restoreVersion = useCallback(async (id: string, versionSeq: number): Promise<boolean> => {
    const response = await fetch(`/api/v1/blog/posts/${id}/versions/${versionSeq}/restore`, { method: 'POST' });

    return response.ok;
  }, []);

  return { posts, loading, error, refresh, loadPost, createPost, savePost, loadHistory, restoreVersion };
}
