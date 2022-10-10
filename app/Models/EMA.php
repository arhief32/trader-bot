<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EMA extends Model
{
    use HasFactory;

    static function calculate($data, int $ema1, int $ema2)
    {
        $data_close = [];
        foreach($data as $row_close){
            array_push($data_close, $row_close['close']);
        }

        $ema1_value = MACD::calculateEMA($data_close, $ema1);
        $ema2_value = MACD::calculateEMA($data_close, $ema2);
        
        for($i = 0; $i < count($data); $i++) {
            $ema1_value_line = $ema1_value[$i];
            $ema2_value_line = $ema2_value[$i];
            
            $data[$i]['ema']['periode'] = $ema1.' & '.$ema2;
            $data[$i]['ema']['ema1'] = sprintf('%.8f', $ema1_value_line);
            $data[$i]['ema']['ema2'] = sprintf('%.8f', $ema2_value_line);
            
            if($i != 0){
                // if($data[$i-1]['ema']['ema1'] < $data[$i-1]['ema']['ema2'] && $data[$i]['ema']['ema1'] > $data[$i]['ema']['ema2']){
                //     $data[$i]['ema']['status'] = 'BUY';
                // } else if($data[$i-1]['ema']['ema1'] > $data[$i-1]['ema']['ema2'] && $data[$i]['ema']['ema1'] < $data[$i]['ema']['ema2']){
                //     $data[$i]['ema']['status'] = 'SELL';
                // } else {
                //     $data[$i]['ema']['status'] = 'NONE';
                // }

                if($data[$i]['ema']['ema1'] > $data[$i]['ema']['ema2']){
                    $data[$i]['ema']['status'] = 'BUY';
                } else if($data[$i]['ema']['ema1'] < $data[$i]['ema']['ema2']){
                    $data[$i]['ema']['status'] = 'SELL';
                }
            }
        }

        return $data;
    }
    static function calculateEMA(array $dps, int $m_range)
    {
        $k = 2/($m_range + 1);
        $ema_dps[0] = $dps[0];
        for ($i = 1; $i < count($dps); $i++) {
            $row_ema_dps = (float)$dps[$i] * (float)$k + (float)$ema_dps[$i - 1] * (1 - (float)$k);
            array_push($ema_dps, round((float)$row_ema_dps, 8));
        }
        return $ema_dps;
    }
}
