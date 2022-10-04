<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\MACD;
use App\Models\RSI;

class CryptoMarket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:cryptomarket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // get all assets from env
        // $assets = env('coins');
        // $assets = explode(',', $assets);
        $assets = getRedis('LIST_ASSETS');

        // check position market
        $position_risk = requestPositionRisk();
        foreach ($position_risk as $row_position_risk) {
            if ((float)$row_position_risk->positionAmt != 0) {
                if (getRedis('POSITION_RISK_' . $row_position_risk->symbol) == false) {
                    setRedis('POSITION_RISK_' . $row_position_risk->symbol, $row_position_risk);
                }
            }
            else {
                deleteRedis('POSITION_RISK_' . $row_position_risk->symbol);
            }
        }

        foreach ($assets as $asset) {
            // get data current asset
            $interval = '1m';
            $limit = '60';
            $response_klines = requestKlines($asset, $interval, $limit);

            // rsi
            $response_klines = RSI::run($response_klines);
            // macd
            $response_klines = MACD::run($response_klines);

            $end_response_klines = end($response_klines);
            if(count(getPrefixRedis('POSITION_RISK_*')) <= 7){
                if (getRedis('POSITION_RISK_' .$asset) == false) {
                    if ($end_response_klines['rsi'] > 70 && $end_response_klines['status'] == 'SELL') {
                        requestMultipleOrders($asset, 'SELL', 20, 0.5);
                    }
                    else if ($end_response_klines['rsi'] < 30 && $end_response_klines['status'] == 'BUY') {
                        requestMultipleOrders($asset, 'BUY', 15, 0.5);
                    }
                }
            }

            // insert log each asset
            $log_path = 'logs/cryptomarket/' . $asset . '/' . $asset . '.log';
            $log_data = end($response_klines);
            insertLogAsset($log_path, $log_data);
        }
    }
}