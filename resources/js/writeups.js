import { mockWriteups } from './mockWriteups.js';

export const PAGE_SIZE = 12;
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

/**
 * @returns {Promise<{ items: object[], total: number, hasMore: boolean }>}
 * simulateError: '1' = semua request gagal, '2' = hanya halaman ≥ 2 yang gagal.
 */
export async function fetchWriteups({
  q = '',
  category = '',
  difficulty = '',
  tags = [],
  page = 1,
  simulateError = '',
} = {}) {
  // TODO: ganti dengan fetch ke backend PHP, mis.:
  // const qs = new URLSearchParams({ q, category, difficulty, tags: tags.join(','), page });
  // const res = await fetch(`/api/writeups.php?${qs}`);
  // if (!res.ok) throw new Error('Gagal memuat write-up.');
  // return res.json(); // { items, total, hasMore }
  await wait(400);
  if (simulateError === '1' || (simulateError === '2' && page > 1)) {
    throw new Error('Simulasi error: gagal memuat write-up.');
  }

  const needle = q.trim().toLowerCase();
  const filtered = mockWriteups.filter((w) => {
    if (needle && !`${w.title} ${w.excerpt}`.toLowerCase().includes(needle)) return false;
    if (category && w.category !== category) return false;
    if (difficulty && w.difficulty !== difficulty) return false;
    const all = [...w.challengeTags, ...w.toolTags];
    return tags.every((t) => all.includes(t));
  });

  const start = (page - 1) * PAGE_SIZE;
  const items = filtered.slice(start, start + PAGE_SIZE);
  return { items, total: filtered.length, hasMore: start + items.length < filtered.length };
}