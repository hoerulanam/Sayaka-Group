@extends('layouts.admin')

@section('title', 'Chat dengan ' . $user->name)
@section('page-title', 'Chat dengan ' . $user->name)

@section('content')
@php
    $adminInitial = strtoupper(substr(auth()->user()->name ?? 'A', 0, 1));
    $customerInitial = strtoupper(substr($user->name, 0, 1));
@endphp

<section class="admin-chat-room">
    <div class="admin-chat-room-header">
        <a href="{{ route('admin.chat.index') }}" class="admin-chat-back">Kembali</a>

        <div class="admin-chat-room-user">
            <span class="admin-chat-avatar has-status" aria-hidden="true">
                {{ $customerInitial }}
                <i></i>
            </span>
            <div>
                <span class="admin-chat-eyebrow">Percakapan Customer</span>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <span class="admin-chat-room-badge">{{ $messages->count() }} pesan</span>
        </div>
    </div>

    <div class="admin-chat-window" data-admin-chat-room>
        <div class="admin-chat-messages" id="adminChatMessages" aria-live="polite" data-admin-chat-messages>
            @forelse($messages as $msg)
                @php
                    $isSent = $msg->from_user_id === auth()->id();
                    $senderName = $isSent ? 'Admin' : $user->name;
                    $senderInitial = $isSent ? $adminInitial : $customerInitial;
                @endphp

                <div class="admin-message-row {{ $isSent ? 'is-sent' : 'is-received' }}">
                    @unless($isSent)
                        <span class="admin-message-avatar" aria-hidden="true">{{ $senderInitial }}</span>
                    @endunless

                    <div class="admin-message-stack">
                        <div class="admin-message-bubble">
                            <p>{{ $msg->message }}</p>
                        </div>

                        <div class="admin-message-meta">
                            <span>{{ $senderName }}</span>
                            <time datetime="{{ $msg->created_at->toIso8601String() }}">{{ $msg->created_at->format('d/m/Y H:i') }}</time>
                            @if($isSent)
                                <small>{{ $msg->read_at ? 'Dibaca' : 'Terkirim' }}</small>
                            @endif
                        </div>
                    </div>

                    @if($isSent)
                        <span class="admin-message-avatar is-me" aria-hidden="true">{{ $senderInitial }}</span>
                    @endif
                </div>
            @empty
                <div class="admin-chat-empty">
                    <span>Belum ada pesan</span>
                    <h2>Mulai percakapan dengan customer.</h2>
                    <p>Kirim informasi atau bantuan pertama untuk user ini.</p>
                </div>
            @endforelse
        </div>

        <form action="{{ route('admin.chat.send') }}" method="post" class="admin-chat-form" id="adminChatForm" data-admin-chat-form data-no-loading="true">
            @csrf
            <input type="hidden" name="to_user_id" value="{{ $user->id }}">

            <div class="admin-chat-quick" aria-label="Template balasan cepat">
                <button type="button" data-admin-chat-template="Halo, pesanan Anda sedang kami cek. Mohon tunggu sebentar ya.">Cek pesanan</button>
                <button type="button" data-admin-chat-template="Pesanan Anda sudah dikonfirmasi dan akan segera diproses.">Konfirmasi</button>
                <button type="button" data-admin-chat-template="Terima kasih, kami akan bantu sampai selesai.">Follow up</button>
            </div>

            <label for="admin-chat-input" class="sr-only">Ketik balasan</label>
            <div class="admin-chat-composer">
                <textarea id="admin-chat-input" name="message" rows="1" maxlength="2000" placeholder="Ketik balasan..." required data-admin-chat-input>{{ old('message') }}</textarea>
                <button type="submit" class="btn btn-primary admin-chat-send">Kirim</button>
            </div>
        </form>

        <div id="adminChatErrors">
            @error('message')
                <small class="admin-chat-error">{{ $message }}</small>
            @enderror
            @error('to_user_id')
                <small class="admin-chat-error">{{ $message }}</small>
            @enderror
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const messages = document.getElementById('adminChatMessages');
    const input = document.querySelector('[data-admin-chat-input]');
    const form = document.querySelector('[data-admin-chat-form]');
    const errorTarget = document.getElementById('adminChatErrors');
    const quickButtons = document.querySelectorAll('[data-admin-chat-template]');

    const scrollToBottom = () => {
        if (!messages) {
            return;
        }

        messages.scrollTop = messages.scrollHeight;
    };

    const resizeInput = () => {
        if (!input) {
            return;
        }

        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 132)}px`;
    };

    const replaceFromHtml = html => {
        const documentFragment = new DOMParser().parseFromString(html, 'text/html');
        const nextMessages = documentFragment.getElementById('adminChatMessages');
        const nextErrors = documentFragment.getElementById('adminChatErrors');
        const hasErrors = Boolean(nextErrors?.textContent.trim());

        if (nextMessages && messages) {
            messages.innerHTML = nextMessages.innerHTML;
            scrollToBottom();
        }

        if (nextErrors && errorTarget) {
            errorTarget.innerHTML = nextErrors.innerHTML;
        }

        return hasErrors;
    };

    scrollToBottom();
    resizeInput();

    input?.addEventListener('input', resizeInput);

    quickButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (!input || input.disabled) {
                return;
            }

            input.value = button.dataset.adminChatTemplate || '';
            input.focus();
            resizeInput();
        });
    });

    form?.addEventListener('submit', async event => {
        if (!window.fetch || !form.checkValidity()) {
            return;
        }

        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton?.setAttribute('disabled', 'disabled');
        form.classList.add('is-sending');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const html = await response.text();
            const hasErrors = replaceFromHtml(html);

            if (response.ok && !hasErrors) {
                input.value = '';
                resizeInput();
            }
        } catch (error) {
            form.submit();
        } finally {
            submitButton?.removeAttribute('disabled');
            form.classList.remove('is-sending');
        }
    });

    setInterval(function () {
        const isTyping = input && (document.activeElement === input || input.value.trim() !== '');

        if (!isTyping && window.fetch) {
            fetch(window.location.href, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(replaceFromHtml)
                .catch(() => {});
        }
    }, 12000);
});
</script>
@endpush
