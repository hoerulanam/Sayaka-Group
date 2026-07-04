<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index()
    {
        $adminId = auth()->id();

        $users = User::where('is_admin', false)
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($adminId) {
                $conversation = Message::where(function ($q) use ($user, $adminId) {
                    $q->where('from_user_id', $adminId)->where('to_user_id', $user->id)
                        ->orWhere('from_user_id', $user->id)->where('to_user_id', $adminId);
                });

                $user->unread_count = (clone $conversation)
                    ->where('from_user_id', $user->id)
                    ->where('to_user_id', $adminId)
                    ->whereNull('read_at')
                    ->count();

                $user->messages_count = (clone $conversation)->count();
                $user->latest_message = (clone $conversation)
                    ->with('fromUser')
                    ->latest('created_at')
                    ->first();

                return $user;
            })
            ->sortByDesc(fn ($user) => $user->latest_message?->created_at?->timestamp ?? 0)
            ->values();

        return view('admin.chat.index', compact('users'));
    }

    public function chat(User $user)
    {
        // Mark messages as read
        Message::where('from_user_id', $user->getAttribute('id'))->where('to_user_id', auth()->id())->update(['read_at' => now()]);

        $messages = Message::where(function ($q) use ($user) {
            $q->where('from_user_id', auth()->id())->where('to_user_id', $user->getAttribute('id'))
              ->orWhere('from_user_id', $user->getAttribute('id'))->where('to_user_id', auth()->id());
        })->orderBy('created_at')->get();
        return view('admin.chat.chat', compact('user', 'messages'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'to_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('is_admin', false),
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
