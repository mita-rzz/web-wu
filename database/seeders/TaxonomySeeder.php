<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Web', 'Crypto', 'Pwn', 'Forensics', 'Mobile', 'Reverse'];

        $tags = [
            'SQL Injection', 'XSS', 'IDOR', 'SSRF', 'RCE',
            'LFI', 'JWT', 'RSA', 'Buffer Overflow', 'Packet Analysis',
        ];

        $tools = [
            'Burp Suite', 'Nmap', 'Wireshark', 'Ghidra', 'GDB',
            'CyberChef', 'sqlmap', 'pwntools', 'ffuf', 'Python',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ($tags as $name) {
            Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ($tools as $name) {
            Tool::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}