<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variantService = app(ProductVariantService::class);

        $products = [
            [
                'brand' => 'SummerShoesCare',
                'name' => 'Lem Super Kuat',
                'code' => 'SSC-LEM-001',
                'size' => '100ml',
                'description' => 'Lem perbaikan sepatu untuk sol, upper, dan material kulit sintetis.',
                'thumbnail' => 'images/products/1777860325_LEM_SEPATU.jpg',
                'price' => 25000,
                'stock' => 0,
            ],
            [
                'brand' => 'SummerShoesCare',
                'name' => 'Cat Sepatu',
                'code' => 'SSC-CAT-002',
                'size' => '60ml',
                'description' => 'Cat sepatu untuk memperbarui warna dan tampilan sepatu favorit.',
                'thumbnail' => 'images/products/1777860368_CatSepatu.jpg',
                'price' => 35000,
                'stock' => 0,
            ],
            [
                'brand' => 'SummerLabs',
                'name' => 'Vanilla Cake',
                'code' => 'SL-VAN-001',
                'size' => null,
                'description' => 'Parfum bernuansa manis vanilla cake dengan karakter hangat dan lembut.',
                'thumbnail' => 'images/products/1777860444_VanillaCake.png',
                'price' => 99000,
                'stock' => 0,
            ],
            [
                'brand' => 'SummerLabs',
                'name' => 'Black Opium',
                'code' => 'SL-BOP-002',
                'size' => null,
                'description' => 'Parfum dengan aroma bold, elegan, dan tahan lama untuk aktivitas harian.',
                'thumbnail' => 'images/products/1777860460_BlackOpium.png',
                'price' => 109000,
                'stock' => 0,
            ],
            [
                'brand' => 'NOID',
                'name' => 'Super Cleaner',
                'code' => 'NOID-SC-001',
                'size' => null,
                'description' => 'Pembersih serbaguna untuk kebutuhan rumah dengan formula praktis.',
                'thumbnail' => 'images/products/1777860513_SuperCleaner.jpg',
                'price' => 45000,
                'stock' => 0,
            ],
            [
                'brand' => 'NOID',
                'name' => 'Noid Blast',
                'code' => 'NOID-NB-002',
                'size' => null,
                'description' => 'Cleaner kuat untuk noda membandel dan perawatan permukaan rumah.',
                'thumbnail' => 'images/products/1777860534_NoidBlast.jpg',
                'price' => 55000,
                'stock' => 0,
            ],
            [
                'brand' => 'FORTEGO',
                'name' => 'Black Polish',
                'code' => 'FTG-BP-001',
                'size' => null,
                'description' => 'Polish kendaraan untuk hasil akhir hitam mengilap dan rapi.',
                'thumbnail' => 'images/products/1777860579_BLACK_POLISH.jpg',
                'price' => 65000,
                'stock' => 0,
            ],
            [
                'brand' => 'FORTEGO',
                'name' => 'Metal Polish',
                'code' => 'FTG-MP-002',
                'size' => null,
                'description' => 'Polish untuk permukaan metal agar kembali bersih dan mengilap.',
                'thumbnail' => 'images/products/1777860605_METAL_POLISH.jpg',
                'price' => 75000,
                'stock' => 0,
            ],
        ];

        foreach ($products as $data) {
            $brand = Brand::where('name', $data['brand'])->firstOrFail();

            $product = Product::updateOrCreate(
                ['code' => $data['code']],
                [
                    'brand_id' => $brand->id,
                    'name' => $data['name'],
                    'size' => $data['size'],
                    'slug' => Str::slug($data['name']),
                    'description' => $data['description'],
                    'thumbnail' => $data['thumbnail'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                ]
            );

            if ($variantService->brandUsesSizeVariants($brand)) {
                $variantService->ensureForProduct($product, [
                    '30ml' => $data['price'],
                    '50ml' => $data['price'],
                ]);
            } elseif ($product->requiresColorSelection()) {
                $variantService->ensureForProduct($product);
            } else {
                $variantService->ensureInventoryItems($product);
            }
        }
    }
}
