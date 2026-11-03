import { useCallback, useEffect, useState } from 'react';

export function useSearchParams() {
  const [params, setParamsState] = useState(() => new URLSearchParams(typeof window !== 'undefined' ? window.location.search : ''));

  useEffect(() => {
    const onPopState = () => {
      setParamsState(new URLSearchParams(window.location.search));
    };
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  const setParams = useCallback((updater, { replace = false } = {}) => {
    setParamsState((prev) => {
      const next = typeof updater === 'function' ? updater(prev) : new URLSearchParams(updater);
      const search = next.toString();
      const newUrl = `${window.location.pathname}${search ? `?${search}` : ''}${window.location.hash}`;
      if (replace) {
        window.history.replaceState(null, '', newUrl);
      } else {
        window.history.pushState(null, '', newUrl);
      }
      return next;
    });
  }, []);

  return [params, setParams];
}
