<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'amount', 'total_amount', 'status', 'type', 'payment_path'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}