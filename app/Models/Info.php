<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info extends Model
{
    use HasFactory;
    protected $fillable = [
        'symbol',
        'pair',
        'base_asset',
        'quote_asset',
        'margin_asset',
        'price_precision',
        'quantity_precision',
        'base_asset_precision',
        'quote_precision',
    ];
}