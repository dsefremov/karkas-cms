import { useCallback, useEffect, useState } from 'react';

export function useOperationsCount(enabled: boolean): number | null {
  const [count, setCount] = useState<number | null>(null);

  const load = useCallback(async () => {
    const response = await fetch('/api/v1/admin/platform/operations');
    if (!response.ok) {
      setCount(null);
      return;
    }
    const body = (await response.json()) as { operations?: unknown[] };
    setCount(body.operations?.length ?? 0);
  }, []);

  useEffect(() => {
    if (!enabled) {
      return;
    }
    void load();
  }, [enabled, load]);

  return count;
}
