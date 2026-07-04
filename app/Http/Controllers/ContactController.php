<?php

namespace App\Http\Controllers;

use App\Models\Contacts;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Tampilkan daftar pesan (admin index)
     */
    public function index()
    {
        $messages = Contacts::latest('created_at')->paginate(15);
        return view('admin.contacts.index', compact('messages'));
    }

    /**
     * Tampilkan detail pesan
     */
    public function show(Contacts $contact)
    {
        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * Hapus pesan
     */
    public function destroy(Contacts $contact)
    {
        $contact->delete();
        return redirect()->route('admin.contacts.index')->with('success', 'Pesan berhasil dihapus.');
    }

    /**
     * Hapus semua pesan yang sudah dibaca
     */
    public function destroyAll(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            Contacts::whereIn('id', $ids)->delete();
            return redirect()->route('admin.contacts.index')->with('success', 'Pesan berhasil dihapus.');
        }
        return redirect()->route('admin.contacts.index');
    }
}
