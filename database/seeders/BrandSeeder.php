<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use updateOrCreate so seeding is idempotent (won't create duplicates on re-seed)
        Brand::updateOrCreate(
            ['name' => 'SummerShoesCare'],
            ['logo' => 'images/brands/logo_ssc.png', 'description' => 'Ahlinya Perawatan Sepatu dan Tas No.1 di Indonesia']
        );

        Brand::updateOrCreate(
            ['name' => 'SummerLabs'],
            ['logo' => 'images/brands/logo_summerlabs.png', 'description' => 'Lokal Brand Parfume. The Luxury Of Fragrance, Made Affordable.']
        );

        Brand::updateOrCreate(
            ['name' => 'NOID'],
            ['logo' => 'images/brands/logo_noid.png', 'description' => 'Solusi Praktis, Merawat Rumah. Formula Aman & Ramah Lingkungan.']
        );

        Brand::updateOrCreate(
            ['name' => 'FORTEGO'],
            ['logo' => 'images/brands/logo_fortego.png', 'description' => 'One Stop Auto Care Solution. Auto Detailing dan Perawatan Mobil']
        );
    }
}
