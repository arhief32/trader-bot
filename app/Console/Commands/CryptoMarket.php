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
        $assets = ['bitcoin','ethereum','binance-coin','xrp','cardano','solana','terra-luna','dogecoin','polkadot','polygon','shiba-inu','tron','avalanche','ethereum-classic','litecoin','cosmos','chainlink','monero','algorand','chiliz'];
        
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
            CURLOPT_URL => 'api.coincap.io/v2/assets',
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
        
        // Log::channel('cryptomarket')->info(json_encode($response->data));
        foreach($response->data as $row_client){
            Log::channel('cryptomarket')->info(json_encode($assets));
            foreach($assets as $value)
            {
                // Log::channel('cryptomarket')->info(json_encode($key.' - '.$value));
                if($row_client->id == $value){
                    // market redis
                    // $get_market = Redis::get('market_'.$row_client->id);

                    Redis::set('market_'.$row_client->id, json_encode([
                        'priceUsd' => $row_client,
                        'timestamp' => $response->data,
                    ]));

                    Log::build([
                        'driver' => 'daily',
                        'path' => storage_path('logs/cryptomarket/'.$value.'/'.$value.'.log'),
                        'level' => env('LOG_LEVEL', 'info'),
                        'days' => 14,
                    ])->info(json_encode((array)$row_client->priceUsd));
                }
            }
        }

    }
}
