@extends('layouts.app')

@section('title', 'Profil Saya - SAYAKA GROUP')

@section('content')
@php
    $initial = strtoupper(substr($user->name, 0, 1));
@endphp

<section class="customer-profile-page bg-slate-50 py-10 sm:py-14">
    <div class="mx-auto w-[92%] max-w-[1180px]">
        <div class="customer-profile-hero overflow-hidden rounded-2xl bg-slate-950 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid lg:grid-cols-[1.05fr_0.95fr]">
                <div class="customer-profile-hero-main p-6 sm:p-8 lg:p-10">
                    <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-sm font-extrabold text-slate-200 no-underline transition hover:border-white/30 hover:text-white">
                        Kembali ke Beranda
                    </a>

                    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex min-w-0 items-center gap-5">
                            <div class="customer-profile-avatar grid h-20 w-20 flex-none place-items-center rounded-2xl bg-yellow-300 text-3xl font-extrabold text-slate-950 shadow-lg shadow-yellow-300/20">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-yellow-300">
                                    Akun Pelanggan
                                </span>
                                <h1 class="customer-profile-wrap-text mt-3 truncate text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                                    {{ $user->name }}
                                </h1>
                                <p class="mt-2 break-words text-sm font-medium text-slate-300">
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('orders.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-extrabold text-slate-950 no-underline transition hover:-translate-y-px hover:bg-yellow-300">
                                Pesanan Saya
                            </a>
                            <a href="{{ route('cart.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-white/15 px-4 py-2 text-sm font-extrabold text-white no-underline transition hover:-translate-y-px hover:border-white/40">
                                Keranjang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="customer-profile-stats-panel bg-white/5 p-6 sm:p-8 lg:p-10">
                    <div class="customer-profile-stats grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                            <span class="block text-xs font-extrabold uppercase tracking-normal text-slate-300">Username</span>
                            <strong class="customer-profile-wrap-text mt-2 block truncate text-lg font-extrabold text-white">{{ $user->username ?: '-' }}</strong>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                            <span class="block text-xs font-extrabold uppercase tracking-normal text-slate-300">No. Handphone</span>
                            <strong class="customer-profile-wrap-text mt-2 block truncate text-lg font-extrabold text-white">{{ $user->phone ?: '-' }}</strong>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                            <span class="block text-xs font-extrabold uppercase tracking-normal text-slate-300">Bergabung</span>
                            <strong class="customer-profile-wrap-text mt-2 block truncate text-lg font-extrabold text-white">{{ optional($user->created_at)->format('d M Y') ?: '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="customer-profile-content mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="customer-profile-forms grid gap-6">
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
            </div>

            <aside class="customer-profile-sidebar grid content-start gap-6">
                <div class="customer-profile-quick-card rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5">
                    <h2 class="m-0 text-xl font-extrabold text-slate-950">Akses Cepat</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kelola aktivitas belanja dan komunikasi dari satu tempat.
                    </p>

                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('orders.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-extrabold text-slate-700 no-underline transition hover:border-blue-700/30 hover:bg-blue-50 hover:text-blue-700">
                            <span>Riwayat Pesanan</span>
                            <span aria-hidden="true">-></span>
                        </a>
                        <a href="{{ route('cart.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-extrabold text-slate-700 no-underline transition hover:border-blue-700/30 hover:bg-blue-50 hover:text-blue-700">
                            <span>Keranjang Belanja</span>
                            <span aria-hidden="true">-></span>
                        </a>
                        <a href="{{ route('chat.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-extrabold text-slate-700 no-underline transition hover:border-blue-700/30 hover:bg-blue-50 hover:text-blue-700">
                            <span>Chat Admin</span>
                            <span aria-hidden="true">-></span>
                        </a>
                    </div>
                </div>

                @include('profile.partials.delete-user-form')
            </aside>
        </div>
    </div>
</section>
@endsection
