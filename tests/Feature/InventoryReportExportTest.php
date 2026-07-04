<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\IncomingInventory;
use App\Models\InventoryItem;
use App\Models\OutgoingInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_inventory_report_to_excel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create([
            'name' => 'SummerShoesCare',
            'logo' => 'images/brands/logo_ssc.png',
            'description' => 'Brand test',
        ]);
        $item = InventoryItem::create([
            'brand_id' => $brand->id,
            'name' => 'Lem Sepatu',
            'code' => 'SSC-LEM-001',
            'color' => 'Hitam',
            'stock' => 10,
        ]);

        IncomingInventory::create([
            'inventory_item_id' => $item->id,
            'quantity' => 8,
            'date' => '2026-05-10',
        ]);

        OutgoingInventory::create([
            'inventory_item_id' => $item->id,
            'quantity' => 3,
            'date' => '2026-05-12',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.report'))
            ->assertOk()
            ->assertSee('Warna')
            ->assertSee('Hitam');

        $this->actingAs($admin)
            ->get(route('admin.inventory.report.pdf', [
                'date_start' => '2026-05-01',
                'date_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $response = $this->actingAs($admin)->get(route('admin.inventory.report.excel', [
            'date_start' => '2026-05-01',
            'date_end' => '2026-05-31',
        ]));

        $response->assertOk();

        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('content-type'));
        $this->assertStringContainsString('laporan_inventaris_2026-05-01_sampai_2026-05-31.xls', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Laporan Transaksi Inventaris', $response->getContent());
        $this->assertStringContainsString('Lem Sepatu', $response->getContent());
        $this->assertStringContainsString('Warna', $response->getContent());
        $this->assertStringContainsString('Hitam', $response->getContent());
        $this->assertStringContainsString('Barang Masuk', $response->getContent());
        $this->assertStringContainsString('Barang Keluar', $response->getContent());
    }
}
