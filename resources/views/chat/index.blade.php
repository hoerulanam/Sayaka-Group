@extends('layouts.app')

@section('title', 'Chat dengan Admin')

@section('content')
@php
    $latestMessage = $messages->last();
    $customerInitial = strtoupper(substr(auth()->user()->name ?? 'U', 0, 1));
@endphp

<section class="customer-chat-page">
    <div class="customer-chat-wrap">
        <div class="customer-chat-topbar">
            <div class="customer-chat-peer">
                <span class="customer-chat-avatar is-support" aria-hidden="true">
                    SG
                    <i></i>
                </span>
                <div>
                    <span class="customer-chat-eyebrow">Customer Support</span>
                    <h1>Ruang Chat SAYAKA</h1>
                    <p>Terhubung dengan admin untuk pesanan, produk, dan bantuan belanja.</p>
                </div>
            </div>

            <div class="customer-chat-summary">
                <div>
                    <strong>{{ $messages->count() }}</strong>
                    <span>Total pesan</span>
                </div>
                <div>
                    <strong>{{ $admins->count() }}</strong>
                    <span>Admin aktif</span>
                </div>
            </div>
        </div>

        <div class="customer-chat-shell">
            <aside class="customer-chat-info">
                <div class="customer-chat-info-card">
                    <span class="customer-chat-avatar is-support" aria-hidden="true">SG</span>
                    <div>
                        <h2>SAYAKA Support</h2>
                        <p>Admin siap membantu kebutuhan belanja dan pertanyaan pesanan.</p>
                    </div>
                </div>

                <div class="customer-chat-side-card">
                    <span>Ringkasan</span>
                    <dl>
                        <div>
                            <dt>Terakhir aktif</dt>
                            <dd>{{ $latestMessage ? $latestMessage->created_at->format('d M Y H:i') : 'Belum ada pesan' }}</dd>
                        </div>
                        <div>
                            <dt>Admin tersedia</dt>
                            <dd>{{ $admins->count() }} admin</dd>
                        </div>
                    </dl>
                </div>

                <div class="customer-chat-tags" aria-label="Topik bantuan">
                    <span>Pesanan</span>
                    <span>Produk</span>
                    <span>Pembayaran</span>
                    <span>Pengiriman</span>
                </div>
            </aside>

            <div class="customer-chat-panel" data-chat-room>
                <div class="customer-chat-header">
                    <div class="customer-chat-header-peer">
                        <span class="customer-chat-avatar is-support" aria-hidden="true">
                            SG
                            <i></i>
                        </span>
                        <div>
                            <span class="customer-chat-eyebrow">Percakapan</span>
                            <h2>SAYAKA Support</h2>
                        </div>
                    </div>

                    <div class="customer-chat-header-actions">
                        <span class="customer-chat-status"><i></i>Online</span>
                        <label for="chat-admin-select" class="sr-only">Pilih admin</label>
                        <select id="chat-admin-select" name="to_user_id" form="customerChatForm" required {{ $admins->isEmpty() ? 'disabled' : '' }}>
                            @forelse($admins as $admin)
                                <option value="{{ $admin->id }}" @selected((int) old('to_user_id', $selectedAdminId) === $admin->id)>
                                    {{ $admin->name }}
                                </option>
                            @empty
                                <option value="">Admin belum tersedia</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="customer-chat-messages" id="customerChatMessages" aria-live="polite" data-chat-messages>
                    @forelse($messages as $msg)
                        @php
                            $isSent = $msg->from_user_id === auth()->id();
                            $senderName = $isSent ? 'Anda' : ($msg->fromUser->name ?? 'Admin');
                            $senderInitial = $isSent ? $customerInitial : strtoupper(substr($senderName, 0, 1));
                        @endphp

                        <div class="customer-message-row {{ $isSent ? 'is-sent' : 'is-received' }}">
                            @unless($isSent)
                                <span class="customer-message-avatar" aria-hidden="true">{{ $senderInitial }}</span>
                            @endunless

                            <div class="customer-message-stack">
                                <div class="customer-message-bubble">
                                    <p>{{ $msg->message }}</p>
                                </div>

                                <div class="customer-message-meta">
                                    <span>{{ $senderName }}</span>
                                    <time datetime="{{ $msg->created_at->toIso8601String() }}">{{ $msg->created_at->format('d/m/Y H:i') }}</time>
                                    @if($isSent)
                                        <small>{{ $msg->read_at ? 'Dibaca' : 'Terkirim' }}</small>
                                    @endif
                                </div>
                            </div>

                            @if($isSent)
                                <span class="customer-message-avatar is-me" aria-hidden="true">{{ $senderInitial }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="customer-chat-empty">
                            <span>Mulai percakapan</span>
                            <h2>Belum ada pesan</h2>
                            <p>Kirim pertanyaan pertama Anda kepada admin SAYAKA GROUP.</p>
                        </div>
                    @endforelse
                </div>

                <form action="{{ route('chat.send') }}" method="post" class="customer-chat-form" id="customerChatForm" data-chat-form data-chat-target="customerChatMessages" data-chat-error-target="customerChatErrors" data-no-loading="true">
                    @csrf

                    <div class="customer-chat-quick" aria-label="Template pesan cepat">
                        <button type="button" data-chat-template="Halo admin, saya ingin menanyakan status pesanan saya.">Status pesanan</button>
                        <button type="button" data-chat-template="Halo admin, saya ingin bertanya detail produk ini.">Detail produk</button>
                        <button type="button" data-chat-template="Halo admin, saya membutuhkan bantuan pembayaran.">Pembayaran</button>
                    </div>

                    <label for="customer-chat-input" class="sr-only">Ketik pesan</label>
                    <div class="customer-chat-composer">
                        <textarea id="customer-chat-input" name="message" rows="1" maxlength="2000" placeholder="Ketik pesan..." required data-chat-input {{ $admins->isEmpty() ? 'disabled' : '' }}>{{ old('message') }}</textarea>
                        <button type="submit" class="btn btn-primary customer-chat-send" {{ $admins->isEmpty() ? 'disabled' : '' }}>
                            <span>Kirim</span>
                        </button>
                    </div>

                    <div id="customerChatErrors">
                        @error('message')
                            <small class="customer-chat-error">{{ $message }}</small>
                        @enderror
                        @error('to_user_id')
                            <small class="customer-chat-error">{{ $message }}</small>
                        @enderror
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const messages = document.getElementById('customerChatMessages');
    const input = document.querySelector('[data-chat-input]');
    const form = document.querySelector('[data-chat-form]');
    const errorTarget = document.getElementById('customerChatErrors');
    const quickButtons = document.querySelectorAll('[data-chat-template]');

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
        const nextMessages = documentFragment.getElementById('customerChatMessages');
        const nextErrors = documentFragment.getElementById('customerChatErrors');
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

            input.value = button.dataset.chatTemplate || '';
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
