import { CloseIcon } from './Icons.jsx';

export default function ActiveFilterChips({ chips }) {
  if (chips.length === 0) return null;
  return (
    <ul className="chips" aria-label="Filter aktif">
      {chips.map((c) => (
        <li key={c.id}>
          <button type="button" className="chip chip--removable" onClick={c.onRemove} aria-label={`Hapus filter ${c.label}`}>
            <span className="chip__label">{c.label}</span>
            <CloseIcon />
          </button>
        </li>
      ))}
    </ul>
  );
}