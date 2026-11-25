<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use App\Models\Writeup;
use App\Models\WriteupView;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WriteupSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'author@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $samples = [
            [
                'title' => 'Blind SQL Injection di Login Portal',
                'author' => $author, 'category' => 'web', 'difficulty' => WriteupDifficulty::Medium,
                'status' => WriteupStatus::Published, 'days_ago' => 10,
                'tags' => ['sql-injection'], 'tools' => ['burp-suite', 'sqlmap'],
                'summary' => 'Form login rentan terhadap SQL injection berbasis waktu.',
                'steps' => ['Intercept request login dengan Burp Suite.', 'Uji payload berbasis waktu pada parameter `username`.', 'Ekstrak password admin dengan sqlmap.'],
            ],
            [
                'title' => 'RSA dengan Modulus Kecil',
                'author' => $author, 'category' => 'crypto', 'difficulty' => WriteupDifficulty::Easy,
                'status' => WriteupStatus::Published, 'days_ago' => 8,
                'tags' => ['rsa'], 'tools' => ['python', 'cyberchef'],
                'summary' => 'Modulus RSA cukup kecil sehingga bisa difaktorkan langsung.',
                'steps' => ['Baca nilai `n`, `e`, dan `c` dari soal.', 'Faktorkan `n` menjadi `p` dan `q`.', 'Hitung `d` lalu dekripsi `c`.'],
            ],
            [
                'title' => 'Stack Overflow Dasar dengan ret2win',
                'author' => $author, 'category' => 'pwn', 'difficulty' => WriteupDifficulty::Medium,
                'status' => WriteupStatus::Published, 'days_ago' => 6,
                'tags' => ['buffer-overflow'], 'tools' => ['gdb', 'pwntools'],
                'summary' => 'Binary tanpa proteksi stack memungkinkan overwrite alamat return.',
                'steps' => ['Cari offset crash dengan pola siklik di GDB.', 'Ambil alamat fungsi `win`.', 'Kirim payload dengan pwntools.'],
            ],
            [
                'title' => 'Menemukan Flag di Capture Jaringan',
                'author' => $author, 'category' => 'forensics', 'difficulty' => WriteupDifficulty::Easy,
                'status' => WriteupStatus::Published, 'days_ago' => 4,
                'tags' => ['packet-analysis'], 'tools' => ['wireshark'],
                'summary' => 'Flag tersembunyi di dalam trafik HTTP pada file pcap.',
                'steps' => ['Buka file pcap di Wireshark.', 'Filter dengan `http`.', 'Follow TCP stream dan baca isinya.'],
            ],
            [
                'title' => 'Bypass JWT dengan Algoritma none',
                'author' => $admin, 'category' => 'web', 'difficulty' => WriteupDifficulty::Hard,
                'status' => WriteupStatus::Published, 'days_ago' => 2,
                'tags' => ['jwt'], 'tools' => ['burp-suite', 'python'],
                'summary' => 'Server menerima token tanpa tanda tangan.',
                'steps' => ['Decode token dan ubah header `alg` menjadi `none`.', 'Ubah klaim `role` menjadi `admin`.', 'Kirim token ke endpoint admin.'],
            ],
            [
                'title' => 'Catatan SSRF (draft)',
                'author' => $author, 'category' => 'web', 'difficulty' => WriteupDifficulty::Hard,
                'status' => WriteupStatus::Draft, 'days_ago' => null,
                'tags' => ['ssrf'], 'tools' => ['ffuf'],
                'summary' => 'Draft yang belum selesai.',
                'steps' => ['Masih ditulis.'],
            ],
        ];

        $categories = ['web', 'crypto', 'pwn', 'forensics', 'mobile', 'reverse'];
        $difficulties = [WriteupDifficulty::Easy, WriteupDifficulty::Medium, WriteupDifficulty::Hard, WriteupDifficulty::Insane];
        $tagSlugs = ['sql-injection', 'xss', 'idor', 'ssrf', 'rce', 'lfi', 'jwt', 'rsa', 'buffer-overflow', 'packet-analysis'];
        $toolSlugs = ['burp-suite', 'nmap', 'wireshark', 'ghidra', 'gdb', 'cyberchef', 'sqlmap', 'pwntools', 'ffuf', 'python'];

        foreach (range(1, 12) as $i) {
            $samples[] = [
                'title' => "Contoh Write-up CTF #{$i}",
                'author' => $author,
                'category' => $categories[($i - 1) % count($categories)],
                'difficulty' => $difficulties[($i - 1) % count($difficulties)],
                'status' => WriteupStatus::Published,
                'days_ago' => 12 + $i,
                'tags' => [$tagSlugs[($i - 1) % count($tagSlugs)]],
                'tools' => [$toolSlugs[($i - 1) % count($toolSlugs)]],
                'summary' => 'Write-up contoh untuk menguji tampilan arsip.',
                'steps' => ['Analisis soal.', 'Temukan celah.', 'Ambil flag.'],
            ];
        }

        foreach ($samples as $data) {
            $writeup = Writeup::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'author_id' => $data['author']->id,
                    'category_id' => Category::where('slug', $data['category'])->value('id'),
                    'title' => $data['title'],
                    'content_md' => $this->markdown($data['summary'], $data['steps']),
                    'difficulty' => $data['difficulty'],
                    'status' => $data['status'],
                    'published_at' => $data['days_ago'] === null ? null : now()->subDays($data['days_ago']),
                ],
            );

            $writeup->tags()->sync(Tag::whereIn('slug', $data['tags'])->pluck('id'));
            $writeup->tools()->sync(Tool::whereIn('slug', $data['tools'])->pluck('id'));

            if ($writeup->status === WriteupStatus::Published && ! $writeup->views()->exists()) {
                $rows = [];

                foreach (range(1, random_int(5, 60)) as $_) {
                    $rows[] = [
                        'writeup_id' => $writeup->id,
                        'viewed_at' => now()->subMinutes(random_int(1, 43200)),
                    ];
                }

                WriteupView::insert($rows);
            }
        }
    }

    /**
     * @param  list<string>  $steps
     */
    private function markdown(string $summary, array $steps): string
    {
        $list = collect($steps)
            ->map(fn (string $step, int $i): string => ($i + 1).'. '.$step)
            ->implode("\n");

        return "## Ringkasan\n\n{$summary}\n\n## Langkah\n\n{$list}\n\n## Flag\n\n`CTF{contoh_flag}`\n";
    }
}