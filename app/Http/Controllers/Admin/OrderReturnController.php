<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\OrderReturn;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderReturnController extends Controller
{
    public function __construct(private ProductVariantService $variantService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $returnsQuery = OrderReturn::with(['order', 'user', 'order.items.product'])
            ->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected', 'completed'], true)) {
            $returnsQuery->where('status', $status);
        }

        $returns = $returnsQuery->paginate(15)->withQueryString();

        $counts = [
            'all' => OrderReturn::count(),
            'pending' => OrderReturn::where('status', 'pending')->count(),
            'approved' => OrderReturn::where('status', 'approved')->count(),
            'rejected' => OrderReturn::where('status', 'rejected')->count(),
            'completed' => OrderReturn::where('status', 'completed')->count(),
        ];

        return view('admin.returns.index', compact('returns', 'counts', 'status'));
    }

    public function approve(Request $request, OrderReturn $orderReturn)
    {
        if ($orderReturn->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan retur berstatus pending yang bisa disetujui.');
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $adminNote = $validated['admin_note'] ?? 'Pengajuan pengembalian barang disetujui. Silakan kemaskan dan kirimkan barang ke alamat gudang SAYAKA GROUP.';

        $orderReturn->update([
            'status' => 'approved',
            'admin_note' => $adminNote,
            'reviewed_at' => now(),
        ]);

        // Send auto message to customer
        $order = $orderReturn->order;
        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $orderReturn->user_id,
            'message' => "Pengajuan pengembalian barang untuk pesanan {$order->displayCode()} telah DISETUJUI Admin.\nCatatan Admin: {$adminNote}",
        ]);

        return back()->with('success', "Pengajuan pengembalian pesanan {$order->displayCode()} telah disetujui.");
    }

    public function reject(Request $request, OrderReturn $orderReturn)
    {
        if ($orderReturn->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan retur berstatus pending yang bisa ditolak.');
        }

        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ], [
            'admin_note.required' => 'Masukkan alasan penolakan pengembalian barang.',
        ]);

        $orderReturn->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'reviewed_at' => now(),
        ]);

        // Send auto message to customer
        $order = $orderReturn->order;
        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $orderReturn->user_id,
            'message' => "Pengajuan pengembalian barang untuk pesanan {$order->displayCode()} DITOLAK Admin.\nAlasan Penolakan: {$validated['admin_note']}",
        ]);

        return back()->with('success', "Pengajuan pengembalian pesanan {$order->displayCode()} telah ditolak.");
    }

    public function complete(Request $request, OrderReturn $orderReturn)
    {
        if ($orderReturn->status !== 'approved') {
            return back()->with('error', 'Hanya pengajuan retur yang disetujui yang bisa ditandai selesai.');
        }

        $validated = $request->validate([
            'refund_proof_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'refund_amount' => 'nullable|numeric|min:0',
        ], [
            'refund_proof_image.image' => 'File bukti transfer refund harus berupa gambar.',
            'refund_proof_image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'refund_proof_image.max' => 'Ukuran gambar maksimal 3 MB.',
            'refund_amount.numeric' => 'Nominal refund harus berupa angka.',
        ]);

        $refundProofPath = $orderReturn->refund_proof_image;

        if ($request->hasFile('refund_proof_image')) {
            $folderPath = public_path('images/refunds');
            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $file = $request->file('refund_proof_image');
            $filename = time() . '_refund_' . Str::slug($orderReturn->order?->displayCode() ?? 'SYK') . '.' . $file->getClientOriginalExtension();
            $file->move($folderPath, $filename);
            $refundProofPath = 'images/refunds/' . $filename;
        }

        $refundAmount = $validated['refund_amount'] ?? $orderReturn->order?->total ?? 0;

        DB::transaction(function () use ($orderReturn, $refundProofPath, $refundAmount) {
            $orderReturn->update([
                'status' => 'completed',
                'refund_proof_image' => $refundProofPath,
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
                'completed_at' => now(),
            ]);

            $order = $orderReturn->order;
            $order->update(['status' => 'returned']);

            // Restorasi stok
            foreach ($order->items as $item) {
                if ($item->productVariant) {
                    $item->productVariant->increment('stock', $item->quantity);
                    $this->variantService->syncProductStock($item->product);
                } elseif ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $amountFormatted = number_format($refundAmount, 0, ',', '.');
            Message::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $orderReturn->user_id,
                'message' => "Barang pengembalian untuk pesanan {$order->displayCode()} telah kami terima dan pengembalian dana sebesar Rp {$amountFormatted} telah diproses oleh Admin. Bukti transfer dapat dilihat pada halaman detail pesanan.",
            ]);
        });

        return back()->with('success', 'Pengembalian barang dan transfer refund berhasil diselesaikan!');
    }
}
