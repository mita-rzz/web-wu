import { useEffect, useRef, useState } from 'react';
import { useDebouncedValue } from "../hooks/useDebouncedValue";
export default function SearchBar({ q, onSearch }) {
  const [text, setText] = useState(q);
  const debounced = useDebouncedValue(text, 300);
  const qRef = useRef(q);
  qRef.current = q;

  // Sinkronkan input saat URL berubah (Back/Forward, reset, hapus chip).
  useEffect(() => {
    setText((t) => (t.trim() === q ? t : q));
  }, [q]);

  // Debounce → URL (replace agar riwayat tidak penuh).
  useEffect(() => {
    const next = debounced.trim();
    if (next !== qRef.current) onSearch(next, { replace: true });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debounced]);

  const submit = (e) => {
    e.preventDefault();
    onSearch(text.trim());
  };

  return (
    <form className="searchbar" role="search" onSubmit={submit}>
      <label className="sr-only" htmlFor="q">Cari write-up</label>
      <input
        id="q"
        name="q"
        className="input"
        type="search"
        inputMode="search"
        enterKeyHint="search"
        autoComplete="off"
        spellCheck={false}
        placeholder="Cari write-up, mis. SQL injection…"
        value={text}
        onChange={(e) => setText(e.target.value)}
      />
      <button type="submit" className="btn btn--primary">Cari</button>
    </form>
  );
}