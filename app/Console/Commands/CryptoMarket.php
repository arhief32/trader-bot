<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
        // $assets = env('ASSETS');
        // $assets = explode(',', $assets);
        // $assets = ['bitcoin','ethereum','binance-coin','xrp','cardano','solana','terra-luna','dogecoin','polkadot','polygon','shiba-inu','tron','avalanche','ethereum-classic','litecoin','cosmos','chainlink','monero','algorand','chiliz'];
        $assets = ['BTCUSDT','ETHUSDT','BNBUSDT','XRPUSDT','ADAUSDT','SOLUSDT','LUNAUSDT','DOGEUSDT','DOTUSDT','MATICUSDT','SHIBUSDT','TRXUSDT','AVAXUSDT','ETCUSDT','LTCUSDT','ATOMUSDT','LINKUSDT','XMRUSDT','ALGOUSDT','CHZUSDT'];
        
        foreach($assets as $asset){
            // request to get price real-time
            $client_average_price = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.binance.com/api',
                'verify'=> false,
                'debug' => false, // optional
            ]);
            $request_average_price = $client_average_price->request('GET', '/api/v3/avgPrice?symbol='.$asset);
            $response_average_price = json_decode($request_average_price->getBody());
            
            Log::channel('cryptomarket')->info(json_encode($response_average_price));

            // get market redis
            $get_market = Redis::get('market_'.$asset);
            $get_market = json_decode($get_market);
            $get_market == null ? $asset_prices = [] : $asset_prices = $get_market;
                    
            // // insert to row market
            $response_average_price->price > 1 ? $response_average_price->price = round($response_average_price->price, 4) : false;
            $response_average_price->price < 1 && $response_average_price->price > 0.1 ? $response_average_price->price = round($response_average_price->price, 4) : false;
            $response_average_price->price < 0.1 && $response_average_price->price > 0.01 ? $response_average_price->price = round($response_average_price->price, 5) : false;
            $response_average_price->price < 0.01 && $response_average_price->price > 0.001 ? $response_average_price->price = round($response_average_price->price, 6) : false;
            $response_average_price->price < 0.001 && $response_average_price->price > 0.0001 ? $response_average_price->price = round($response_average_price->price, 7) : false;
            $asset_price = [
                'price_usd' => $response_average_price->price,
            ];
            array_push($asset_prices, $asset_price);

            // // remove if > 100
            if(count($asset_prices)  > 7){
                array_shift($asset_prices);
            }

            // // calculate indicator
            $array_indicator = [];
            if($asset_prices != null || $asset_prices != 0){
                foreach($asset_prices as $row_asset){
                    if(isset($row_asset->price_usd)){
                        array_push($array_indicator, $row_asset->price_usd);
                    }
                }
            }
            $rsi = trader_rsi($array_indicator, 6);
            isset($rsi[6]) ? $rsi = $rsi[6] : $rsi = false;
            
            // update market in redis (del and then set)
            Redis::del('market_'.$asset);
            Redis::set('market_'.$asset, json_encode($asset_prices));

            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/cryptomarket/'.$asset.'/'.$asset.'.log'),
                'level' => env('LOG_LEVEL', 'info'),
                'days' => 14,
            ])->info(json_encode([
                'price_usd' => $asset_price['price_usd'],
                'rsi' => $rsi,
                'array_indicator' => $array_indicator,
            ]));
        }
    }
}