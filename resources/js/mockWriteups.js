const slugify = (t) =>
  t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80);

const w = (title, excerpt, username, category, difficulty, challengeTags, toolTags, views, createdAt) => ({
  title, slug: slugify(title), excerpt,
  author: { username, avatar: null },
  category, difficulty, challengeTags, toolTags, views, createdAt,
});

const raw = [
  w('Blind SQLi pada panel login', 'Menemukan celah blind SQL injection lewat selisih waktu respons, lalu mengekstrak hash admin karakter demi karakter.', 'nadia_r', 'Web', 'easy', ['SQLi'], ['BurpSuite'], 4210, '2026-10-20'),
  w('Dari self-XSS yang diabaikan menjadi account takeover penuh: rantai bug lintas subdomain, cookie scoping yang salah, dan bypass CSP lewat endpoint JSONP lama yang lupa dimatikan', 'Write-up bug bounty tentang bagaimana tiga temuan berseverity rendah digabung menjadi satu kompromi akun lengkap, termasuk timeline pelaporan dan respons vendor.', 'bayu.sec', 'Web', 'insane', ['XSS', 'IDOR', 'JWT'], ['BurpSuite', 'CyberChef'], 18930, '2026-10-19'),
  w('RSA dengan e kecil', 'Soal klasik: pesan pendek dan eksponen publik 3 membuat akar pangkat tiga langsung membuka plaintext.', 'kirana', 'Crypto', 'easy', ['RSA'], [], 980, '2026-10-18'),
  w('ROP chain pada binary tanpa PIE', 'Menyusun rantai ROP untuk memanggil system("/bin/sh") dengan gadget dari libc yang bocor lewat puts.', 'dimas_0x', 'Pwn', 'medium', ['ROP'], ['GDB', 'pwntools', 'Ghidra'], 7345, '2026-10-17'),
  w('Memory dump: mencari proses tersembunyi', 'Menganalisis dump RAM Windows untuk menemukan proses yang disembunyikan rootkit sederhana.', 'rafi.k', 'Forensics', 'medium', [], ['Volatility'], 2210, '2026-10-16'),
  w('Hooking pengecekan root dengan Frida', 'Melewati deteksi root pada aplikasi Android dengan skrip Frida beberapa baris.', 'salsa', 'Mobile', 'easy', [], ['Frida'], 3120, '2026-10-15'),
  w('SSRF ke metadata cloud', 'Memanfaatkan fitur pratinjau URL untuk membaca kredensial instance dari endpoint metadata internal.', 'ghostbyte', 'Web', 'hard', ['SSRF'], ['BurpSuite'], 11260, '2026-10-14'),
  w('Heap overflow di allocator kustom', 'Mengeksploitasi allocator buatan sendiri yang tidak memeriksa ukuran chunk, hingga memperoleh primitif tulis sembarang.', 'tegar', 'Pwn', 'insane', ['RCE'], ['GDB', 'pwntools', 'Ghidra', 'CyberChef'], 6050, '2026-10-13'),
  w('JWT alg=none', 'Server menerima token tanpa tanda tangan, sehingga klaim role bisa diubah menjadi admin.', 'lintang', 'Web', 'easy', ['JWT'], ['CyberChef'], 5400, '2026-10-12'),
  w('Menelusuri exfiltrasi DNS di PCAP', 'Data dikirim keluar lewat subdomain panjang. Berikut cara memilahnya di Wireshark dan merakit ulang berkasnya.', 'arka', 'Forensics', 'medium', [], ['Wireshark', 'CyberChef'], 2780, '2026-10-11'),
  w('Padding oracle pada cookie sesi', 'Memulihkan plaintext cookie terenkripsi CBC tanpa kunci, satu blok demi satu blok.', 'nadia_r', 'Crypto', 'hard', [], ['BurpSuite', 'CyberChef'], 4890, '2026-10-10'),
  w('IDOR pada endpoint invoice', 'Mengubah satu angka pada URL membuka faktur pengguna lain.', 'kirana', 'Web', 'easy', ['IDOR'], ['BurpSuite'], 0, '2026-10-09'),
  w('Reversing validator lisensi dengan Ghidra', 'Membaca pseudo-code, menemukan checksum kustom, lalu menulis keygen singkat.', 'dimas_0x', 'Pwn', 'medium', [], ['Ghidra', 'GDB'], 3333, '2026-10-08'),
  w('Unpacking APK yang dilindungi', 'Melepas lapisan packer, memetakan kelas yang dimuat dinamis, dan menemukan kunci API yang tertanam.', 'salsa', 'Mobile', 'hard', ['RCE'], ['Frida', 'Ghidra'], 5120, '2026-10-07'),
  w('Stego pada PNG: LSB dan chunk tambahan', 'Dua lapis penyembunyian dalam satu gambar.', 'rafi.k', 'Forensics', 'easy', [], ['CyberChef'], 1500, '2026-10-06'),
  w('Wiener attack untuk RSA dengan d kecil', 'Pecahan berlanjut dari n dan e mengungkap kunci privat yang terlalu kecil.', 'bayu.sec', 'Crypto', 'medium', ['RSA'], [], 2640, '2026-10-05'),
  w('XSS tersimpan di kolom komentar', 'Filter hanya menghapus tag script, sehingga atribut event handler tetap lolos dan mencuri token sesi.', 'ghostbyte', 'Web', 'easy', ['XSS'], ['BurpSuite'], 8120, '2026-10-04'),
  w('Format string untuk membocorkan canary', 'Membocorkan stack canary dan alamat basis lewat printf tanpa format, lalu menimpa return address.', 'tegar', 'Pwn', 'hard', ['ROP'], ['GDB', 'pwntools'], 3970, '2026-10-03'),
  w('Remote code execution lewat deserialisasi tidak aman pada layanan internal yang tidak pernah diaudit sejak 2019', 'Payload serial berbahaya memicu eksekusi perintah. Dilengkapi proof of concept dan rekomendasi perbaikan.', 'lintang', 'Web', 'hard', ['RCE', 'SSRF'], ['BurpSuite', 'CyberChef', 'GDB'], 14420, '2026-10-02'),
  w('Carving berkas dari image disk', 'Memulihkan dokumen yang sudah dihapus dari image disk yang rusak sebagian.', 'arka', 'Forensics', 'medium', [], ['Volatility', 'CyberChef'], 1980, '2026-10-01'),
  w('Intercept traffic aplikasi dengan pinning', 'Mematikan certificate pinning agar lalu lintas HTTPS aplikasi terlihat di proxy.', 'salsa', 'Mobile', 'medium', [], ['Frida', 'BurpSuite'], 4410, '2026-09-30'),
  w('Vigenère dengan kunci berulang', 'Analisis frekuensi dan uji Kasiski untuk memulihkan panjang kunci.', 'kirana', 'Crypto', 'easy', [], ['CyberChef'], 760, '2026-09-29'),
  w('ret2libc tanpa one-gadget', 'Membangun pemanggilan libc manual lengkap dengan penyelarasan stack.', 'dimas_0x', 'Pwn', 'medium', ['ROP'], ['GDB', 'pwntools'], 2890, '2026-09-28'),
  w('Blind SSRF dengan out-of-band', 'Mengonfirmasi SSRF lewat callback DNS ketika respons tidak pernah ditampilkan.', 'bayu.sec', 'Web', 'hard', ['SSRF'], ['BurpSuite'], 6720, '2026-09-27'),
  w('Timeline serangan dari log Windows', 'Menyusun urutan kejadian dari event log: logon, persistence, lalu pergerakan lateral.', 'rafi.k', 'Forensics', 'hard', [], ['Volatility', 'Wireshark'], 3050, '2026-09-26'),
  w('Bypass biometrik lewat intent yang diekspos', 'Activity yang diekspor tanpa permission memungkinkan melompati layar otentikasi.', 'ghostbyte', 'Mobile', 'insane', ['IDOR'], ['Frida', 'Ghidra', 'BurpSuite'], 9210, '2026-09-25'),
  w('Hash length extension pada tanda tangan URL', 'Menambahkan parameter ke URL bertanda tangan tanpa mengetahui secret, memakai kelemahan Merkle–Damgård.', 'tegar', 'Crypto', 'hard', [], ['CyberChef'], 3440, '2026-09-24'),
  w('SQLi union-based pada filter produk', 'Menentukan jumlah kolom, lalu membaca tabel pengguna lewat UNION SELECT.', 'nadia_r', 'Web', 'medium', ['SQLi'], ['BurpSuite'], 12880, '2026-09-23'),
  w('Smoke test_https___contoh.example/path/yang/sangat/panjang/tanpa/spasi/untuk/menguji/overflow', 'Entri uji untuk memastikan token panjang tanpa spasi tidak merusak tata letak kartu.', 'lintang', 'Crypto', 'insane', ['RSA', 'JWT'], ['CyberChef', 'GDB', 'Ghidra', 'pwntools'], 120, '2026-09-22'),
];

export const mockWriteups = raw
  .map((r, i) => ({ id: i + 1, ...r }))
  .sort((a, b) => b.createdAt.localeCompare(a.createdAt));