@extends('layouts.admin')

@section('title', 'Chat dengan User')
@section('page-title', 'Chat dengan User')

@section('content')
<section class="admin-chat-list-page">
    <div class="admin-chat-overview">
        <div>
            <span class="admin-chat-eyebrow">Inbox Customer</span>
            <h2>Daftar percakapan customer</h2>
            <p>Pantau pesan masuk, prioritas unread, dan lanjutkan percakapan dari satu tempat.</p>
        </div>

        <div class="admin-chat-stats">
            <div>
                <strong>{{ $users->count() }}</strong>
                <span>User</span>
            </div>
            <div>
                <strong>{{ $users->sum('unread_count') }}</strong>
                <span>Unread</span>
            </div>
        </div>
    </div>

    <div class="admin-chat-user-grid">
        @forelse($users as $user)
            <a href="{{ route('admin.chat.show', $user) }}" class="admin-chat-user-card {{ $user->unread_count > 0 ? 'has-unread' : '' }}">
                <div class="admin-chat-user-top">
                    <span class="admin-chat-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <div>
                        <h3>{{ $user->name }}</h3>
                        <p>{{ $user->email }}</p>
                    </div>
                    @if($user->unread_count > 0)
                        <strong class="admin-chat-unread">{{ $user->unread_count }}</strong>
                    @endif
                </div>

                <div class="admin-chat-preview">
                    @if($user->latest_message)
                        <span>{{ $user->latest_message->from_user_id === auth()->id() ? 'Anda' : $user->latest_message->fromUser?->name }}:</span>
                        <p>{{ \Illuminate\Support\Str::limit($user->latest_message->message, 100) }}</p>
                        <time datetime="{{ $user->latest_message->created_at->toIso8601String() }}">
                            {{ $user->latest_message->created_at->format('d M Y H:i') }}
                        </time>
                    @else
                        <span>Belum ada percakapan</span>
                        <p>Klik untuk mulai mengirim pesan kepada user ini.</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="admin-chat-empty">
                <span>Belum ada user</span>
                <h2>Daftar customer masih kosong.</h2>
                <p>User yang sudah terdaftar akan muncul di halaman ini.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
