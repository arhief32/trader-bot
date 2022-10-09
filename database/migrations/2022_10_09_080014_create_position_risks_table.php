<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Symbol           string  `json:"symbol" gorm:"primaryKey"`
	    // PositionAmt      string  `json:"positionAmt"`
	    // EntryPrice       string  `json:"entryPrice"`
	    // MarkPrice        string  `json:"markPrice"`
	    // UnRealizedProfit string  `json:"unRealizedProfit"`
	    // LiquidationPrice string  `json:"liquidationPrice"`
	    // Leverage         string  `json:"leverage"`
	    // MaxNotionalValue string  `json:"maxNotionalValue"`
	    // MarginType       string  `json:"marginType"`
	    // IsolatedMargin   string  `json:"isolatedMargin"`
	    // IsAutoAddMargin  string  `json:"isAutoAddMargin"`
	    // PositionSide     string  `json:"positionSide"`
	    // Notional         string  `json:"notional"`
	    // IsolatedWallet   string  `json:"isolatedWallet"`
	    // UpdateTime       float64 `json:"updateTime"`
        Schema::create('position_risks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->string('position_amount')->nullable();
            $table->string('entry_price')->nullable();
            $table->string('mark_price')->nullable();
            $table->string('unrealized_profit')->nullable();
            $table->string('liquidation_price')->nullable();
            $table->string('leverage')->nullable();
            $table->string('max_notional_value')->nullable();
            $table->string('margin_type')->nullable();
            $table->string('isolated_margin')->nullable();
            $table->string('is_auto_add_margin')->nullable();
            $table->string('position_side')->nullable();
            $table->string('notional')->nullable();
            $table->string('isolated_wallet')->nullable();
            $table->float('update_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_risks');
    }
};
