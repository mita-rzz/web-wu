import { useCallback, useEffect, useRef, useState } from 'react';
import { fetchWriteups } from '../../writeups.js';

const INITIAL = {
  items: [], total: 0, hasMore: false, page: 0,
  status: 'loading', loadingMore: false, loadMoreError: false,
};

export function useWriteups(filters) {
  const key = JSON.stringify(filters);
  const [attempt, setAttempt] = useState(0);
  const [state, setState] = useState(INITIAL);
  const reqId = useRef(0);
  const busy = useRef(false);
  const stateRef = useRef(state);
  stateRef.current = state;

  useEffect(() => {
    const id = ++reqId.current;
    busy.current = false;
    setState(INITIAL);
    fetchWriteups({ ...JSON.parse(key), page: 1 }).then(
      (r) => {
        if (id !== reqId.current) return;
        setState({ ...INITIAL, items: r.items, total: r.total, hasMore: r.hasMore, page: 1, status: 'success' });
      },
      () => {
        if (id === reqId.current) setState((s) => ({ ...s, status: 'error' }));
      }
    );
  }, [key, attempt]);

  const loadMore = useCallback(() => {
    const s = stateRef.current;
    if (busy.current || s.status !== 'success' || !s.hasMore) return;
    busy.current = true;
    const id = reqId.current;
    setState((p) => ({ ...p, loadingMore: true, loadMoreError: false }));
    fetchWriteups({ ...JSON.parse(key), page: s.page + 1 }).then(
      (r) => {
        if (id !== reqId.current) return;
        busy.current = false;
        setState((p) => ({
          ...p, items: [...p.items, ...r.items], total: r.total,
          hasMore: r.hasMore, page: p.page + 1, loadingMore: false,
        }));
      },
      () => {
        if (id !== reqId.current) return;
        busy.current = false;
        setState((p) => ({ ...p, loadingMore: false, loadMoreError: true }));
      }
    );
  }, [key]);

  const retry = useCallback(() => setAttempt((a) => a + 1), []);
  return { ...state, loadMore, retry };
}