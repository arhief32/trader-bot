<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionRisk extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'position_amount',
        'entry_price',
        'mark_price',
        'unrealized_profit',
        'liquidation_price',
        'leverage',
        'max_notional_value',
        'margin_type',
        'isolated_margin',
        'is_auto_add_margin',
        'position_side',
        'notional',
        'isolated_wallet',
        // 'update_time',
    ];

}
