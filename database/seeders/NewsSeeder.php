<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Peluncuran Produk Baru SummerShoesCare',
                'content' => '<p>Kami dengan bangga mengumumkan peluncuran produk perawatan sepatu terbaru dari SummerShoesCare. Nikmati formula baru yang lebih tahan lama dan ramah lingkungan.</p>',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'SummerLabs Hadir dengan Aroma Musim Panas',
                'content' => '<p>SummerLabs menghadirkan rangkaian parfum edisi spesial musim panas yang ringan dan menyegarkan.</p>',
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Tips Perawatan Rumah Bersama NOID',
                'content' => '<p>NOID berbagi tips praktis merawat rumah dan menjaga kebersihan dengan aman untuk keluarga.</p>',
                'published_at' => now(),
            ],
        ];

        foreach ($items as $n) {
            News::updateOrCreate(
                ['slug' => Str::slug($n['title'])],
                [
                    'title' => $n['title'],
                    'content' => $n['content'],
                    'thumbnail' => null,
                    'published_at' => $n['published_at'],
                ]
            );
        }
    }
}
