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
        
        /**guzzle */
        // get data assets from coincap
        // $client = new \GuzzleHttp\Client([
        //     'base_uri' => env('COINCAP_URL'),
        //     'verify'=> false,
        //     'debug' => false, // optional
        // ]);
        // $request = $client->request('GET', '/v2/assets',
        // [
        //     'headers' => [
        //         'Authorization' => 'Bearer '.env('COINCAP_API_KEY'),
        //     ],
        // ]);
        // $response = json_decode($request->getBody());
        /**end guzzle */

        /**curl */
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.coincap.io/v2/markets?exchangeId=binance&quoteSymbol=USDT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer b6f8499c-2c50-4f83-bb4c-397a3abec5e3'
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        /**end curl */
        
        foreach($response->data as $row_client){
            // get market redis
            $get_market = Redis::get('market_'.$row_client->baseSymbol);
            $get_market = json_decode($get_market);
            $get_market == null ? $assets = [] : $assets = $get_market;
                    
            // insert to row market
            $asset = [
                'price_usd' => $row_client->priceUsd,
                // 'volume_usd_24_hours' => $row_client->volumeUsd24Hr,
                'timestamp' => $response->timestamp,
            ];
            array_push($assets, $asset);

            // sort market
            // usort($assets, function($a,$b){
            //     return $a->timestamp <=> $b->timestamp;
            // });

            // remove if > 100
            if(count($assets)  > 20){
                array_shift($assets);
            }

            // calculate indicator
            $array_indicator = [];
            if($assets != null || $assets != 0){
                foreach($assets as $row_asset){
                    if(isset($row_asset->price_usd)){
                        array_push($array_indicator, $row_asset->price_usd);
                    }
                }
            }
            $rsi = trader_rsi($array_indicator, 6);
            isset($rsi[6]) ? $rsi = $rsi[6] : $rsi = false;
            
            // update market in redis (del and then set)
            Redis::del('market_'.$row_client->baseSymbol);
            Redis::set('market_'.$row_client->baseSymbol, json_encode($assets));

            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/cryptomarket/'.$row_client->baseSymbol.'/'.$row_client->baseId.'.log'),
                'level' => env('LOG_LEVEL', 'info'),
                'days' => 14,
            ])->info(json_encode([
                'price_usd' => $row_client->priceUsd,
                'updated_at' => date('Y-m-d h:i:s', $row_client->updated/1000),
                'rsi' => $rsi,
            ]));
        }
    }
}