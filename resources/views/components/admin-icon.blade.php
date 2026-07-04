@props(['name', 'label' => null])

<svg
    {{ $attributes->merge(['class' => 'admin-ui-icon']) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if($label)
        role="img"
        aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
>
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" />
            @break

        @case('news')
            <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 1-3-3V5Z" />
            <path d="M8 9h6" />
            <path d="M8 13h8" />
            <path d="M8 17h5" />
            @break

        @case('products')
            <path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z" />
            <path d="M4 7.5V16l8 5 8-5V7.5" />
            <path d="M12 12v9" />
            @break

        @case('stock')
            <rect x="3" y="4" width="8" height="7" rx="1.5" />
            <rect x="13" y="4" width="8" height="7" rx="1.5" />
            <rect x="8" y="13" width="8" height="7" rx="1.5" />
            @break

        @case('inventory')
            <path d="M3 10 12 4l9 6" />
            <path d="M5 10v10h14V10" />
            <path d="M9 20v-6h6v6" />
            <path d="M8 10h8" />
            @break

        @case('orders')
            <path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 0 1 2-2Z" />
            <path d="M9 8h6" />
            <path d="M9 12h6" />
            <path d="M9 16h4" />
            @break

        @case('paid')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M3 10h18" />
            <path d="m8 15 2 2 5-5" />
            @break

        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
            <circle cx="9.5" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            @break

        @case('contacts')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
            @break

        @case('chat')
            <path d="M21 12a8 8 0 0 1-8 8H7l-4 3 1.5-5A8 8 0 1 1 21 12Z" />
            <path d="M8 12h.01" />
            <path d="M12 12h.01" />
            <path d="M16 12h.01" />
            @break

        @case('website')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18" />
            <path d="M12 3a14 14 0 0 1 0 18" />
            <path d="M12 3a14 14 0 0 0 0 18" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4" />
            <path d="M12 16h.01" />
    @endswitch
</svg>
