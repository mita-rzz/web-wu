import { AlertIcon } from './Icons.jsx';

export default function ErrorState({ onRetry }) {
  return (
    <div className="state state--error" role="alert">
      <AlertIcon />
      <h3 className="state__title">Gagal memuat write-up</h3>
      <p>Terjadi kendala saat mengambil data. Periksa koneksi lalu coba lagi.</p>
      <button type="button" className="btn btn--secondary" onClick={onRetry}>Coba lagi</button>
    </div>
  );
}
