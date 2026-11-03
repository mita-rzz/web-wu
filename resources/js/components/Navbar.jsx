import { Link } from '@inertiajs/react';

export default function Navbar() {
  return (
    <header className="navbar">
      <nav className="container navbar__inner" aria-label="Utama">
        <Link href="/" className="navbar__brand" translate="no">SolveLog</Link>
        <ul className="navbar__links">
          <li><Link className="navbar__link" href="/">Beranda</Link></li>
          <li><a className="navbar__link" href="#filter">Kategori</a></li>
          <li><Link className="navbar__link" href="/login">Login</Link></li>
        </ul>
      </nav>
    </header>
  );
}