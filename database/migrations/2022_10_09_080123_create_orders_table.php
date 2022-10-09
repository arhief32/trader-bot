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
        // ClientOrderId string  `json:"clientOrderId" gorm:"primaryKey"`
	    // AvgPrice      string  `json:"avgPrice"`
	    // ClosePosition bool    `json:"closePosition"`
	    // CumQty        string  `json:"cumQty"`
	    // CumQuote      string  `json:"cumQuote"`
	    // ExecutedQty   string  `json:"executedQty"`
	    // OrderId       float64 `json:"orderId"`
	    // OrigQty       string  `json:"origQty"`
	    // OrigType      string  `json:"origType"`
	    // PositionSide  string  `json:"positionSide"`
	    // Price         string  `json:"price"`
	    // PriceProtect  bool    `json:"priceProtect"`
	    // ReduceOnly    bool    `json:"reduceOnly"`
	    // Side          string  `json:"side"`
	    // Status        string  `json:"status"`
	    // StopPrice     string  `json:"stopPrice"`
	    // Symbol        string  `json:"symbol"`
	    // TimeInForce   string  `json:"timeInForce"`
	    // Type          string  `json:"type"`
	    // PpdateTime    float64 `json:"updateTime"`
	    // WorkingType   string  `json:"workingType"`
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_order_id')->unique();
            $table->string('average_price')->nullable();
            $table->boolean('close_position')->nullable();
            $table->string('cumulative_quantity')->nullable();
            $table->string('cumulative_quote')->nullable();
            $table->string('executed_quantity')->nullable();
            $table->string('order_id')->nullable();
            $table->string('origin_quantity')->nullable();
            $table->string('origin_type')->nullable();
            $table->string('position_side')->nullable();
            $table->string('price')->nullable();
            $table->boolean('price_protect')->nullable();
            $table->boolean('reduce_only')->nullable();
            $table->string('side')->nullable();
            $table->string('status')->nullable();
            $table->string('stop_price')->nullable();
            $table->string('symbol')->nullable();
            $table->string('time_in_force')->nullable();
            $table->string('type')->nullable();
            $table->float('update_time')->nullable();
            $table->string('working_type')->nullable();
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
        Schema::dropIfExists('orders');
    }
};
