<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'status',
        'payment_method',
        'payment_status',
        'midtrans_order_id',
        'midtrans_snap_token',
        'midtrans_redirect_url',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'midtrans_transaction_status',
        'midtrans_fraud_status',
        'midtrans_status_code',
        'midtrans_status_message',
        'midtrans_payload',
        'paid_at',
        'shipping_address',
        'phone',
        'shipping_carrier',
        'shipping_service',
        'shipping_cost'
    ];

    protected $casts = [
        'midtrans_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function displayCode(): string
    {
        $date = $this->created_at?->format('Ymd') ?? now()->format('Ymd');
        $sequence = str_pad((string) ($this->getKey() ?? 0), 6, '0', STR_PAD_LEFT);

        return "SYK-{$date}-{$sequence}";
    }
}
