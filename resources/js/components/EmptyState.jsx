import { SearchOffIcon } from './Icons.jsx';

export default function EmptyState({ onReset }) {
  return (
    <div className="state">
      <SearchOffIcon />
      <h3 className="state__title">Tidak ada write-up ditemukan</h3>
      <p>Coba kata kunci lain atau longgarkan filter yang sedang aktif.</p>
      <button type="button" className="btn btn--secondary" onClick={onReset}>Reset filter</button>
    </div>
  );
}