<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingInventory extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'quantity',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
