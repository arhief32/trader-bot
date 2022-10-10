<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_order_id',
        'average_price',
        'close_position',
        'cumulative_quantity',
        'cumulative_quote',
        'executed_quantity',
        'order_id',
        'origin_quantity',
        'origin_type',
        'position_side',
        'price',
        'price_protect',
        'reduce_only',
        'side',
        'status',
        'stop_price',
        'symbol',
        'time_in_force',
        'type',
        'working_type',
    ];
}