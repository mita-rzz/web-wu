import { useCallback, useEffect, useMemo, useRef } from 'react';
import { Head } from '@inertiajs/react';
import { useSearchParams } from '../hooks/useSearchParams.js';
import Navbar from '../components/Navbar.jsx';
import SearchBar from '../components/SearchBar.jsx';
import FilterPanel from '../components/FilterPanel.jsx';
import ActiveFilterChips from '../components/ActiveFilterChips.jsx';
import WriteupGrid from '../components/WriteupGrid.jsx';
import EmptyState from '../components/EmptyState.jsx';
import ErrorState from '../components/ErrorState.jsx';
import { useWriteups } from '../hooks/useWriteups.js';
import { CATEGORIES, DIFFICULTIES, tagLabel } from '../constants.js';

const numFmt = new Intl.NumberFormat('id-ID');

export default function Dashboard() {
  const [params, setParams] = useSearchParams();

  const filters = useMemo(() => {
    const category = params.get('category') ?? '';
    const difficulty = params.get('difficulty') ?? '';
    return {
      q: params.get('q') ?? '',
      category: CATEGORIES.includes(category) ? category : '',
      difficulty: DIFFICULTIES.some((d) => d.value === difficulty) ? difficulty : '',
      tags: (params.get('tags') ?? '').split(',').filter(Boolean),
      // Dev only: ?simulateError=1 (semua gagal) atau =2 (load more gagal)
      simulateError: params.get('simulateError') ?? '',
    };
  }, [params]);

  const { q, category, difficulty, tags } = filters;
  const { items, total, status, hasMore, loadingMore, loadMoreError, loadMore, retry } = useWriteups(filters);

  const update = useCallback(
    (patch: Record<string, string | string[]>, { replace = false } = {}) => {
      setParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          for (const [k, v] of Object.entries(patch)) {
            const value = Array.isArray(v) ? v.join(',') : v;
            if (value) next.set(k, value);
            else next.delete(k);
          }
          return next;
        },
        { replace }
      );
    },
    [setParams]
  );

  const toggleTag = useCallback(
    (t: string) => update({ tags: tags.includes(t) ? tags.filter((x: string) => x !== t) : [...tags, t] }),
    [tags, update]
  );
  const reset = () => update({ q: '', category: '', difficulty: '', tags: [] });

  const activeCount = (category ? 1 : 0) + (difficulty ? 1 : 0) + tags.length;
  const hasActive = activeCount > 0 || q !== '';

  const chips: { id: string; label: string; onRemove: () => void }[] = [];
  if (q) chips.push({ id: 'q', label: `Kata kunci: “${q}”`, onRemove: () => update({ q: '' }) });
  if (category) chips.push({ id: 'cat', label: `Kategori: ${category}`, onRemove: () => update({ category: '' }) });
  if (difficulty) {
    const label = DIFFICULTIES.find((d) => d.value === difficulty)?.label ?? difficulty;
    chips.push({ id: 'diff', label: `Level: ${label}`, onRemove: () => update({ difficulty: '' }) });
  }
  tags.forEach((t: string) => chips.push({ id: `tag-${t}`, label: tagLabel(t), onRemove: () => toggleTag(t) }));

  // Infinite scroll
  const sentinel = useRef<HTMLDivElement | null>(null);
  useEffect(() => {
    const el = sentinel.current;
    if (!el || !hasMore || loadMoreError || loadingMore || status !== 'success') return undefined;
    const io = new IntersectionObserver(([e]) => e.isIntersecting && loadMore(), { rootMargin: '400px 0px' });
    io.observe(el);
    return () => io.disconnect();
  }, [hasMore, loadMoreError, loadingMore, status, items.length, loadMore]);

  const countText =
    status === 'loading' ? 'Memuat…' : status === 'error' ? '' : `${numFmt.format(total)} write-up ditemukan`;

  const moreLabel = loadingMore ? 'Memuat…' : loadMoreError ? 'Gagal memuat — coba lagi' : 'Muat lebih banyak';

  const pageTitle = q ? `“${q}” — CyberNotes` : 'Beranda — CyberNotes';

  return (
    <>
      <Head title={pageTitle} />
      <a className="skip-link" href="#main">Skip to content</a>
      <Navbar />
      <main id="main" tabIndex={-1}>
        <section className="container hero">
          <p className="eyebrow">Write-up CTF &amp; Bug Bounty</p>
          <h1 className="hero__title">Arsip Write-Up CTF & Bug Bounty.</h1>
          <p className="lead">
            Telusuri write-up CTF, bug bounty, dan eksploitasi keamanan siber dari komunitas.
          </p>
        </section>

        <section className="search" aria-label="Pencarian dan filter">
          <div className="container search__inner">
            <SearchBar q={q} onSearch={(value: string, opts?: { replace?: boolean }) => update({ q: value }, opts)} />
            <FilterPanel
              filters={filters}
              onChange={update}
              onToggleTag={toggleTag}
              onReset={reset}
              hasActive={hasActive}
              activeCount={activeCount}
            />
            <ActiveFilterChips chips={chips} />
          </div>
        </section>

        <section className="container feed" aria-busy={status === 'loading'}>
          <div className="feed__head">
            <h2 className="feed__title">Write-up</h2>
            <p className="feed__count" aria-live="polite" aria-atomic="true">{countText}</p>
          </div>

          {status === 'loading' && <WriteupGrid items={[]} skeletonCount={6} />}
          {status === 'error' && <ErrorState onRetry={retry} />}
          {status === 'success' && items.length === 0 && <EmptyState onReset={reset} />}
          {status === 'success' && items.length > 0 && (
            <>
              <WriteupGrid items={items} skeletonCount={loadingMore ? 3 : 0} />
              <div className="feed__more">
                {hasMore ? (
                  <>
                    <div ref={sentinel} aria-hidden="true" />
                    <button
                      type="button"
                      className="btn btn--outline"
                      aria-disabled={loadingMore}
                      onClick={() => !loadingMore && loadMore()}
                    >
                      {moreLabel}
                    </button>
                  </>
                ) : (
                  <p className="feed__end">Kamu sudah sampai di akhir daftar.</p>
                )}
              </div>
            </>
          )}
        </section>
      </main>
    </>
  );
}
