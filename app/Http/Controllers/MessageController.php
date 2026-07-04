<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index()
    {
        $admins = User::where('is_admin', true)->orderBy('name')->get();

        Message::where('to_user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::where(function ($q) {
            $q->where('from_user_id', Auth::id())->orWhere('to_user_id', Auth::id());
        })->with('fromUser', 'toUser')->orderBy('created_at')->get();

        $latestMessage = $messages->last();
        $selectedAdminId = $admins->first()?->id;

        if ($latestMessage) {
            $selectedAdminId = $latestMessage->from_user_id === Auth::id()
                ? $latestMessage->to_user_id
                : $latestMessage->from_user_id;
        }

        return view('chat.index', compact('admins', 'messages', 'selectedAdminId'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'to_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('is_admin', true),
            ],
            'message' => 'required|string|max:2000',
        ]);

        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $request->to_user_id,
            'message' => $request->message,
        ]);
        return back();
    }
}
