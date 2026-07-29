<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'description',
        'proof_image',
        'status',
        'admin_note',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'refund_proof_image',
        'refund_amount',
        'reviewed_at',
        'completed_at',
        'refunded_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Konfirmasi Admin',
            'approved' => 'Disetujui Admin',
            'rejected' => 'Ditolak Admin',
            'completed' => 'Pengembalian Selesai',
            default => ucfirst($this->status),
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'pending' => 'is-pending',
            'approved' => 'is-approved',
            'rejected' => 'is-rejected',
            'completed' => 'is-completed',
            default => 'is-default',
        };
    }

    public function proofImageUrl(): ?string
    {
        return $this->proof_image ? asset($this->proof_image) : null;
    }

    public function refundProofImageUrl(): ?string
    {
        return $this->refund_proof_image ? asset($this->refund_proof_image) : null;
    }

    public function hasBankDetails(): bool
    {
        return filled($this->bank_name) && filled($this->bank_account_number) && filled($this->bank_account_holder);
    }
}
