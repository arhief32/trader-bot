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
        // Symbol             string `gorm:"primaryKey"`
	    // Pair               string
	    // BaseAsset          string
	    // QuoteAsset         string
	    // MarginAsset        string
	    // PricePrecision     int64
	    // QuantityPrecision  int64
	    // BaseAssetPrecision int64
	    // QuotePrecision     int64
        Schema::create('infos', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->string('pair')->nullable();
            $table->string('base_asset')->nullable();
            $table->string('quote_asset')->nullable();
            $table->string('margin_asset')->nullable();
            $table->integer('price_precision')->nullable();
            $table->integer('quantity_precision')->nullable();
            $table->integer('base_asset_precision')->nullable();
            $table->integer('quote_precision')->nullable();
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
        Schema::dropIfExists('infos');
    }
};
