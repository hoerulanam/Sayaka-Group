<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderReturnController extends Controller
{
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (! $order->canBeReturned()) {
            return back()->with('error', 'Pesanan ini tidak dapat diajukan pengembalian.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:Barang Rusak/Cacat,Tidak Sesuai Pesanan,Ukuran/Varian Salah,Lainnya',
            'description' => 'required|string|max:1000',
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'bank_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:100',
        ], [
            'reason.required' => 'Pilih alasan pengembalian barang.',
            'reason.in' => 'Alasan pengembalian tidak valid.',
            'description.required' => 'Berikan deskripsi detail mengenai alasan pengembalian.',
            'proof_image.required' => 'Unggah foto bukti kondisi barang.',
            'proof_image.image' => 'File bukti harus berupa gambar.',
            'proof_image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'proof_image.max' => 'Ukuran gambar maksimal 3 MB.',
            'bank_name.required' => 'Pilih atau isi nama bank / e-wallet tujuan refund.',
            'bank_account_number.required' => 'Isi nomor rekening atau nomor e-wallet.',
            'bank_account_holder.required' => 'Isi nama pemilik rekening sesuai di buku tabungan/aplikasi.',
        ]);

        $proofImagePath = null;

        if ($request->hasFile('proof_image')) {
            $folderPath = public_path('images/returns');
            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $file = $request->file('proof_image');
            $filename = time() . '_' . Str::slug($order->displayCode()) . '.' . $file->getClientOriginalExtension();
            $file->move($folderPath, $filename);
            $proofImagePath = 'images/returns/' . $filename;
        }

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'proof_image' => $proofImagePath,
            'bank_name' => $validated['bank_name'],
            'bank_account_number' => $validated['bank_account_number'],
            'bank_account_holder' => $validated['bank_account_holder'],
            'status' => 'pending',
        ]);

        // Send auto message to admin chat
        $adminUser = User::where('is_admin', true)->first();

        if ($adminUser) {
            Message::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $adminUser->id,
                'message' => "Pengajuan retur barang untuk pesanan {$order->displayCode()} telah dikirim. Alasan: {$validated['reason']}. Rekening Refund: {$validated['bank_name']} - {$validated['bank_account_number']} a/n {$validated['bank_account_holder']}. Mohon konfirmasi dari Admin.",
            ]);
        }

        return back()->with('success', 'Pengajuan pengembalian barang & data rekening refund berhasil dikirim.');
    }

    public function updateBankDetails(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $orderReturn = $order->orderReturn;
        if (! $orderReturn || $orderReturn->status === 'completed') {
            return back()->with('error', 'Data rekening tidak dapat diubah.');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:100',
        ], [
            'bank_name.required' => 'Pilih atau isi nama bank / e-wallet.',
            'bank_account_number.required' => 'Isi nomor rekening atau e-wallet.',
            'bank_account_holder.required' => 'Isi nama pemilik rekening.',
        ]);

        $orderReturn->update([
            'bank_name' => $validated['bank_name'],
            'bank_account_number' => $validated['bank_account_number'],
            'bank_account_holder' => $validated['bank_account_holder'],
        ]);

        return back()->with('success', 'Data rekening pengembalian dana berhasil diperbarui.');
    }
}
