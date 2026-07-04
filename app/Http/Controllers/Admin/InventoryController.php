<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\IncomingInventory;
use App\Models\OutgoingInventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function __construct(private ProductVariantService $variantService)
    {
    }

    public function index()
    {
        $inventoryItems = InventoryItem::with(['brand', 'productVariant'])->get();
        return view('admin.inventory.index', compact('inventoryItems'));
    }

    public function incoming()
    {
        $incomingRecords = IncomingInventory::with(['inventoryItem.brand', 'inventoryItem.productVariant'])->latest()->get();
        $inventoryItems = $this->activeInventoryItems()->with(['brand', 'productVariant'])->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::with(['brand', 'variants'])->orderBy('name')->get();

        return view('admin.inventory.incoming', compact('incomingRecords', 'inventoryItems', 'brands', 'products'));
    }

    public function storeIncoming(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'product_id' => 'nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'product_name' => 'required_without_all:inventory_item_id,product_id|nullable|string|max:255',
            'product_code' => 'required_without_all:inventory_item_id,product_id|nullable|string|max:255',
            'size' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1',
            'brand_id' => 'required_without_all:inventory_item_id,product_id|nullable|exists:brands,id',
            'date' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                if (! $request->filled('product_id') && $request->filled('product_variant_id')) {
                    throw ValidationException::withMessages([
                        'product_variant_id' => 'Varian hanya bisa dipilih untuk produk terdaftar.',
                    ]);
                }

                if ($request->filled('inventory_item_id')) {
                    $inventoryItem = InventoryItem::findOrFail($request->inventory_item_id);
                } elseif ($request->filled('product_id')) {
                    $product = Product::with('variants')->findOrFail($request->product_id);
                    $variant = $this->selectedInventoryProductVariant($request, $product);

                    $this->variantService->ensureInventoryItems($product);
                    $inventoryItem = $this->inventoryItemForProduct($product, $variant);
                } else {
                    $inventoryItem = $this->manualInventoryItem($request);
                }

                $inventoryItem->increment('stock', $request->quantity);

                // Simpan riwayat incoming
                IncomingInventory::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => $request->quantity,
                    'date' => $request->date,
                ]);
            });

            return redirect()->route('admin.inventory.incoming')->with('success', 'Barang masuk berhasil ditambahkan.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function outgoing()
    {
        $outgoingRecords = OutgoingInventory::with('inventoryItem.productVariant')->latest()->get();
        $inventoryItems = $this->activeInventoryItems()->with('productVariant')->where('stock', '>', 0)->get();
        return view('admin.inventory.outgoing', compact('outgoingRecords', 'inventoryItems'));
    }

    public function storeOutgoing(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $inventoryItem = InventoryItem::whereKey($request->inventory_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventoryItem->stock < $request->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah barang keluar melebihi stok yang tersedia.',
                    ]);
                }

                $inventoryItem->decrement('stock', $request->quantity);

                OutgoingInventory::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => $request->quantity,
                    'date' => $request->date,
                ]);

                if ($inventoryItem->product_variant_id) {
                    $variant = ProductVariant::whereKey($inventoryItem->product_variant_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $product = Product::whereKey($variant->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $variant->increment('stock', $request->quantity);
                    $this->variantService->syncProductStock($product);

                    return;
                }

                if ($inventoryItem->product_id) {
                    $product = Product::whereKey($inventoryItem->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($product->variants()->exists()) {
                        throw ValidationException::withMessages([
                            'inventory_item_id' => 'Barang inventaris ini belum terhubung ke varian produk.',
                        ]);
                    }

                    $product->increment('stock', $request->quantity);

                    return;
                }

                $brand = Brand::findOrFail($inventoryItem->brand_id);
                $usesSizeVariants = $this->variantService->brandUsesSizeVariants($brand);
                $isShoePaint = Product::isShoePaintInventoryCode($inventoryItem->code);

                $product = Product::where('name', $inventoryItem->name)
                    ->where('brand_id', $inventoryItem->brand_id)
                    ->first();

                if (! $product) {
                    $product = Product::create([
                        'name' => $inventoryItem->name,
                        'code' => $usesSizeVariants ? null : ($isShoePaint ? Product::SHOE_PAINT_CODE : $inventoryItem->code),
                        'size' => $usesSizeVariants ? null : $inventoryItem->size,
                        'slug' => $this->uniqueProductSlug($inventoryItem->name, $inventoryItem->code),
                        'brand_id' => $inventoryItem->brand_id,
                        'stock' => 0,
                        'price' => 0,
                    ]);
                }

                if ($usesSizeVariants || $product->requiresColorSelection() || $product->variants()->exists()) {
                    $variants = $usesSizeVariants || $product->requiresColorSelection()
                        ? $this->variantService->ensureForProduct($product)
                        : $product->variants()->get();
                    $variant = $this->matchingInventoryVariant($variants, $inventoryItem);

                    if (! $variant && ($usesSizeVariants || $product->requiresColorSelection())) {
                        $variantType = $inventoryItem->color ? 'color' : ($inventoryItem->size ? 'size' : null);
                        $variantValue = $inventoryItem->color ?: $inventoryItem->size;

                        if ($variantType && $variantValue) {
                            $variants = $this->variantService->ensureForProduct($product, [], [[
                                'type' => $variantType,
                                'value' => $variantValue,
                                'code' => $inventoryItem->code,
                                'price' => $product->price,
                            ]]);
                            $variant = $this->matchingInventoryVariant($variants, $inventoryItem);
                        }
                    }

                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'inventory_item_id' => 'Varian inventaris tidak valid.',
                        ]);
                    }

                    $inventoryItem->fill([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'brand_id' => $product->brand_id,
                        'name' => $product->name,
                        'code' => $variant->code,
                        'size' => $variant->size ?? ($variant->color && $product->requiresColorSelection() ? $product->size : null),
                        'color' => $variant->color,
                    ])->save();

                    $variant->increment('stock', $request->quantity);
                    $this->variantService->syncProductStock($product);
                } else {
                    $product->increment('stock', $request->quantity);
                }
            });

            return redirect()->route('admin.inventory.outgoing')->with('success', 'Barang keluar berhasil dicatat.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function report()
    {
        $records = $this->inventoryReportRecords();

        return view('admin.inventory.report', compact('records'));
    }

    public function pdfReport(Request $request)
    {
        $validated = $this->validateReportDates($request);

        $start = $validated['date_start'];
        $end = $validated['date_end'];
        $records = $this->inventoryReportRecords($start, $end);

        $pdf = Pdf::loadView('admin.inventory.report-pdf', compact('records', 'start', 'end'));

        return $pdf->download("laporan_inventaris_{$start}_sampai_{$end}.pdf");
    }

    public function excelReport(Request $request)
    {
        $validated = $this->validateReportDates($request);

        $start = $validated['date_start'];
        $end = $validated['date_end'];
        $records = $this->inventoryReportRecords($start, $end);
        $filename = "laporan_inventaris_{$start}_sampai_{$end}.xls";

        return response($this->buildInventoryExcelXml($records, $start, $end), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function validateReportDates(Request $request): array
    {
        return $request->validate([
            'date_start' => 'required|date|date_format:Y-m-d',
            'date_end' => 'required|date|date_format:Y-m-d|after_or_equal:date_start',
        ]);
    }

    private function inventoryReportRecords(?string $start = null, ?string $end = null): Collection
    {
        $incomingQuery = IncomingInventory::with('inventoryItem.productVariant');
        $outgoingQuery = OutgoingInventory::with('inventoryItem.productVariant');

        if ($start && $end) {
            $incomingQuery->whereBetween('date', [$start, $end]);
            $outgoingQuery->whereBetween('date', [$start, $end]);
        }

        $incoming = $incomingQuery->get()->map(function ($item) {
            $item->type = 'Masuk';
            return $item;
        });

        $outgoing = $outgoingQuery->get()->map(function ($item) {
            $item->type = 'Keluar';
            return $item;
        });

        return $incoming
            ->concat($outgoing)
            ->sortByDesc($start && $end ? 'date' : 'created_at')
            ->values();
    }

    private function buildInventoryExcelXml(Collection $records, string $start, string $end): string
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->writePi('mso-application', 'progid="Excel.Sheet"');

        $writer->startElement('Workbook');
        $writer->writeAttribute('xmlns', 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttribute('xmlns:o', 'urn:schemas-microsoft-com:office:office');
        $writer->writeAttribute('xmlns:x', 'urn:schemas-microsoft-com:office:excel');
        $writer->writeAttribute('xmlns:ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttribute('xmlns:html', 'http://www.w3.org/TR/REC-html40');

        $this->writeExcelStyles($writer);

        $writer->startElement('Worksheet');
        $writer->writeAttribute('ss:Name', 'Laporan Inventaris');
        $writer->startElement('Table');

        foreach ([100, 210, 130, 80, 90, 90, 140] as $width) {
            $writer->startElement('Column');
            $writer->writeAttribute('ss:Width', (string) $width);
            $writer->endElement();
        }

        $this->writeExcelRow($writer, ['Laporan Transaksi Inventaris'], 'Title', ['mergeAcross' => 6]);
        $this->writeExcelRow($writer, ['Periode: ' . date('d/m/Y', strtotime($start)) . ' sampai ' . date('d/m/Y', strtotime($end))], 'Subtitle', ['mergeAcross' => 6]);
        $this->writeExcelRow($writer, ['Dicetak pada: ' . now()->format('d/m/Y H:i')], 'Subtitle', ['mergeAcross' => 6]);
        $this->writeExcelRow($writer, []);
        $this->writeExcelRow($writer, ['Tanggal', 'Nama Barang', 'Kode Barang', 'Varian', 'Tipe', 'Jumlah', 'Keterangan'], 'Header');

        foreach ($records as $record) {
            $this->writeExcelRow($writer, [
                \Carbon\Carbon::parse($record->date)->format('d/m/Y'),
                $record->inventoryItem->name ?? '-',
                $record->inventoryItem->code ?? '-',
                $record->inventoryItem->variantLabel() ?? '-',
                $record->inventoryItem->variantLabel() ? $record->inventoryItem->variantAttributeName() : '-',
                (int) $record->quantity,
                $record->type === 'Masuk' ? 'Barang Masuk' : 'Barang Keluar',
            ], 'Body');
        }

        if ($records->isEmpty()) {
            $this->writeExcelRow($writer, ['Tidak ada data untuk periode ini.'], 'Body', ['mergeAcross' => 6]);
        }

        $this->writeExcelRow($writer, []);
        $this->writeExcelRow($writer, ['Ringkasan', '', '', '', '', '', ''], 'Header');
        $this->writeExcelRow($writer, ['Total Barang Masuk', '', '', '', '', (int) $records->where('type', 'Masuk')->sum('quantity'), ''], 'Body');
        $this->writeExcelRow($writer, ['Total Barang Keluar', '', '', '', '', (int) $records->where('type', 'Keluar')->sum('quantity'), ''], 'Body');
        $this->writeExcelRow($writer, ['Total Transaksi', '', '', '', '', $records->count(), ''], 'Body');

        $writer->endElement(); // Table
        $writer->endElement(); // Worksheet
        $writer->endElement(); // Workbook
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function writeExcelStyles(\XMLWriter $writer): void
    {
        $writer->startElement('Styles');

        $styles = [
            'Title' => ['bold' => true, 'size' => '16', 'color' => '#172033', 'fill' => '#FFFFFF'],
            'Subtitle' => ['bold' => false, 'size' => '10', 'color' => '#64748B', 'fill' => '#FFFFFF'],
            'Header' => ['bold' => true, 'size' => '10', 'color' => '#FFFFFF', 'fill' => '#132033'],
            'Body' => ['bold' => false, 'size' => '10', 'color' => '#172033', 'fill' => '#FFFFFF'],
        ];

        foreach ($styles as $id => $style) {
            $writer->startElement('Style');
            $writer->writeAttribute('ss:ID', $id);
            $writer->startElement('Font');
            $writer->writeAttribute('ss:Bold', $style['bold'] ? '1' : '0');
            $writer->writeAttribute('ss:Size', $style['size']);
            $writer->writeAttribute('ss:Color', $style['color']);
            $writer->endElement();
            $writer->startElement('Interior');
            $writer->writeAttribute('ss:Color', $style['fill']);
            $writer->writeAttribute('ss:Pattern', 'Solid');
            $writer->endElement();
            $writer->startElement('Borders');
            foreach (['Bottom', 'Left', 'Right', 'Top'] as $position) {
                $writer->startElement('Border');
                $writer->writeAttribute('ss:Position', $position);
                $writer->writeAttribute('ss:LineStyle', 'Continuous');
                $writer->writeAttribute('ss:Weight', '1');
                $writer->writeAttribute('ss:Color', '#E2E8F0');
                $writer->endElement();
            }
            $writer->endElement();
            $writer->startElement('Alignment');
            $writer->writeAttribute('ss:Vertical', 'Top');
            $writer->writeAttribute('ss:WrapText', '1');
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function writeExcelRow(\XMLWriter $writer, array $values, ?string $style = null, array $options = []): void
    {
        $writer->startElement('Row');

        if (empty($values)) {
            $writer->endElement();
            return;
        }

        foreach ($values as $index => $value) {
            $writer->startElement('Cell');

            if ($style) {
                $writer->writeAttribute('ss:StyleID', $style);
            }

            if ($index === 0 && isset($options['mergeAcross'])) {
                $writer->writeAttribute('ss:MergeAcross', (string) $options['mergeAcross']);
            }

            $writer->startElement('Data');
            $writer->writeAttribute('ss:Type', is_numeric($value) ? 'Number' : 'String');
            $writer->text((string) $value);
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return redirect()->route('admin.inventory.index')->with('success', 'Barang berhasil dihapus.');
    }

    private function uniqueProductSlug(string $name, ?string $code): string
    {
        $base = Str::slug($name . '-' . $code);
        $slug = $base;
        $suffix = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function manualInventoryItem(Request $request): InventoryItem
    {
        $brand = Brand::findOrFail($request->brand_id);
        $name = trim((string) $request->product_name);
        $code = strtoupper(trim((string) $request->product_code));
        $size = $request->filled('size') ? trim((string) $request->size) : null;
        $color = $request->filled('color') ? trim((string) $request->color) : null;

        if (Product::isShoePaintInventoryCode($code)) {
            if (! $color && str_starts_with($code, Product::SHOE_PAINT_CODE . '-')) {
                $color = Str::title(strtolower(str_replace('-', ' ', substr($code, strlen(Product::SHOE_PAINT_CODE) + 1))));
            }

            if (! $color) {
                throw ValidationException::withMessages([
                    'color' => 'Pilih warna Cat Sepatu untuk barang masuk.',
                ]);
            }

            $product = Product::where('brand_id', $brand->id)
                ->where('code', Product::SHOE_PAINT_CODE)
                ->first();

            if ($product) {
                $variants = $this->variantService->ensureForProduct($product, [], [[
                    'type' => 'color',
                    'value' => $color,
                    'code' => Product::shoePaintVariantCode($color),
                    'price' => $product->price,
                ]]);

                $variant = $this->matchingInventoryVariant($variants, new InventoryItem([
                    'code' => Product::shoePaintVariantCode($color),
                    'color' => $color,
                ]));

                return $this->inventoryItemForProduct($product, $variant);
            }

            $code = Product::shoePaintVariantCode($color);
        } elseif ($this->variantService->brandUsesSizeVariants($brand) && $size) {
            $product = Product::where('brand_id', $brand->id)
                ->where('name', $name)
                ->first();

            if ($product) {
                $variants = $this->variantService->ensureForProduct($product, [], [[
                    'type' => 'size',
                    'value' => $size,
                    'code' => $code,
                    'price' => $product->price,
                ]]);

                $variant = $this->matchingInventoryVariant($variants, new InventoryItem([
                    'code' => $code,
                    'size' => $size,
                ]));

                return $this->inventoryItemForProduct($product, $variant);
            }
        }

        $inventoryItem = InventoryItem::where('brand_id', $brand->id)
            ->where('name', $name)
            ->where('code', $code)
            ->where('size', $size)
            ->where('color', $color)
            ->first();

        if (! $inventoryItem) {
            $inventoryItem = InventoryItem::create([
                'product_id' => null,
                'product_variant_id' => null,
                'name' => $name,
                'code' => $code,
                'size' => $size,
                'color' => $color,
                'brand_id' => $brand->id,
                'stock' => 0,
            ]);
        }

        return $inventoryItem;
    }

    private function matchingInventoryVariant(Collection $variants, InventoryItem $inventoryItem): ?ProductVariant
    {
        if ($inventoryItem->color) {
            return $variants->first(fn ($variant) => $variant->color && strcasecmp($variant->color, $inventoryItem->color) === 0);
        }

        if ($inventoryItem->size) {
            return $variants->first(fn ($variant) => $variant->size && strcasecmp($variant->size, $inventoryItem->size) === 0);
        }

        if ($inventoryItem->code) {
            return $variants->first(fn ($variant) => $variant->code && strcasecmp($variant->code, $inventoryItem->code) === 0);
        }

        return null;
    }

    private function selectedInventoryProductVariant(Request $request, Product $product): ?ProductVariant
    {
        if (! $product->hasVariants()) {
            if ($request->filled('product_variant_id')) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Produk ini tidak memiliki varian.',
                ]);
            }

            return null;
        }

        if (! $request->filled('product_variant_id')) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Pilih varian produk untuk barang masuk.',
            ]);
        }

        $variant = $product->variants->firstWhere('id', (int) $request->product_variant_id);

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Varian produk yang dipilih tidak valid.',
            ]);
        }

        return $variant;
    }

    private function inventoryItemForProduct(Product $product, ?ProductVariant $variant): InventoryItem
    {
        if ($variant) {
            $item = InventoryItem::firstOrNew([
                'product_variant_id' => $variant->id,
            ]);

            $item->fill([
                'product_id' => $product->id,
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => $variant->code,
                'size' => $variant->size ?? ($variant->color && $product->requiresColorSelection() ? $product->size : null),
                'color' => $variant->color,
            ]);
        } else {
            $item = InventoryItem::firstOrNew([
                'product_id' => $product->id,
                'product_variant_id' => null,
            ]);

            $item->fill([
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => $product->code,
                'size' => $product->size,
                'color' => null,
            ]);
        }

        if (! $item->exists) {
            $item->stock = 0;
        }

        $item->save();

        return $item;
    }

    private function activeInventoryItems()
    {
        return InventoryItem::query()
            ->where(function ($query) {
                $query->where('code', '!=', Product::SHOE_PAINT_CODE)
                    ->orWhereNull('code')
                    ->orWhereNotNull('color');
            });
    }
}
