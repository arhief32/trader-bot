<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MACD extends Model
{
    use HasFactory;
    public static function processMacdData($symbol)
    {
        $data = getRedis('MARKET_' . $symbol);
        $precision = getPrecision($symbol)->price_precision;

        $array_macd = [];

        foreach ($data as $row) {
            array_push($array_macd, [
                'close' => (float)$row,
                'ema_12' => 0,
                'ema_26' => 0,
                'macd' => 0,
                'signal' => 0,
                'histogram' => 0,
            ]);
        }

        // ema12
        $data_12 = array_slice($data, 0, 12);
        $average_12 = array_sum($data_12) / count($data_12);
        $array_macd[11]['ema_12'] = $average_12;
        $next_data_12 = array_slice($data, 12);
        foreach ($next_data_12 as $key_nd12 => $value_nd12) {
            $value_ema_12 = (float)$array_macd[12 + $key_nd12]['close'] * (2 / (12 + 1)) + $array_macd[12 + $key_nd12 - 1]['ema_12'] * (1 - (2 / (12 + 1)));
            $array_macd[12 + $key_nd12]['ema_12'] = round($value_ema_12, $precision);
        }

        // ema26
        $data_26 = array_slice($data, 0, 26);
        $average_26 = array_sum($data_26) / count($data_26);
        $array_macd[25]['ema_26'] = $average_26;
        $next_data_26 = array_slice($data, 26);
        foreach ($next_data_26 as $key_nd26 => $value_nd26) {
            $value_ema_26 = (float)$array_macd[26 + $key_nd26]['close'] * (2 / (26 + 1)) + $array_macd[26 + $key_nd26 - 1]['ema_26'] * (1 - (2 / (26 + 1)));
            $array_macd[26 + $key_nd26]['ema_26'] = round($value_ema_26, $precision);
        }

        // macd
        foreach ($array_macd as $key_macd => $row_macd) {
            if ($row_macd['ema_12'] != 0 && $row_macd['ema_26'] != 0) {
                $array_macd[$key_macd]['macd'] = round($row_macd['ema_12'] - $row_macd['ema_26'], $precision);
            }
        }

        // signal
        $data_macd = array_slice($array_macd, 25, 9);
        $data_9 = [];
        foreach ($data_macd as $row_data_macd) {
            array_push($data_9, $row_data_macd['macd']);
        }
        $average_9 = array_sum($data_9) / count($data_9);
        $array_macd[33]['signal'] = round($average_9, $precision);
        $next_data_9 = array_slice($array_macd, 34);
        foreach ($next_data_9 as $key_nd9 => $value_nd9) {
            $value_signal = (float)$array_macd[34 + $key_nd9]['macd'] * (2 / (9 + 1)) + $array_macd[9 + $key_nd9 - 1]['signal'] * (1 - (2 / (9 + 1)));
            $array_macd[34 + $key_nd9]['signal'] = round($value_signal, $precision);
        }

        // histogram
        foreach ($array_macd as $key_macd => $row_macd) {
            if ($row_macd['macd'] != 0 && $row_macd['signal'] != 0) {
                $array_macd[$key_macd]['histogram'] = round($row_macd['macd'] - $row_macd['signal'], $precision);
            }
        }

        return $array_macd;
    }

    static function lag($ema1 = 12, $ema2 = 26, $signal = 9)
    {
        return (max($ema1, $ema2) + $signal) - 1;
    }

    static function run($data, $ema1 = 12, $ema2 = 26, $signal = 9)
    {

        $smoothing_constant_1 = 2 / ($ema1 + 1);
        $smoothing_constant_2 = 2 / ($ema2 + 1);
        $previous_EMA1 = null;
        $previous_EMA2 = null;

        $ema1_value = null;
        $ema2_value = null;

        $macd_array = array();

        //loop data
        foreach ($data as $key => $row) {

            //ema 1
            if ($key >= $ema1) {

                //first 
                if (!isset($previous_EMA1)) {
                    $sum = 0;
                    for ($i = $key - ($ema1 - 1); $i <= $key; $i++)
                        $sum += $data[$i]['close'];
                    //calc sma
                    $sma = $sum / $ema1;

                    //save
                    $previous_EMA1 = $sma;
                    $ema1_value = $sma;
                }
                else {
                    //ema formula
                    $ema = ($data[$key]['close'] - $previous_EMA1) * $smoothing_constant_1 + $previous_EMA1;

                    //save
                    $previous_EMA1 = $ema;
                    $ema1_value = $ema;
                }
            }

            //ema 2
            if ($key >= $ema2) {

                //first 
                if (!isset($previous_EMA2)) {
                    $sum = 0;
                    for ($i = $key - ($ema2 - 1); $i <= $key; $i++)
                        $sum += $data[$i]['close'];
                    //calc sma
                    $sma = $sum / $ema2;

                    //save
                    $previous_EMA2 = $sma;
                    $ema2_value = $sma;
                }
                else {
                    //ema formula
                    $ema = ($data[$key]['close'] - $previous_EMA2) * $smoothing_constant_2 + $previous_EMA2;

                    //save
                    $previous_EMA2 = $ema;
                    $ema2_value = $ema;
                }
            }

            //check if we have 2 values to calc MACD Line
            if (isset($ema1_value) && isset($ema2_value)) {

                $macd_line = $ema1_value - $ema2_value;

                //add to front
                array_unshift($macd_array, $macd_line);

                //pop back if too long
                if (count($macd_array) > $signal)
                    array_pop($macd_array);

                //save
                $data[$key]['macd_line'] = round($macd_line, 9);
            }

            //have enough data to calc signal sma
            if (count($macd_array) == $signal) {

                //k moving average 
                $sum = array_reduce($macd_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);

                $sma = $sum / $signal;

                //save
                $data[$key]['signal_line'] = round($sma, 9);
            }

            //check if we have 2 values to calc MACD Line
            if (isset($data[$key]['signal_line']) && isset($data[$key-1]['signal_line']) && isset($data[$key]['macd_line']) && isset($data[$key-1]['macd_line'])) {
                if($data[$key-1]['macd_line'] < $data[$key-1]['signal_line'] && $data[$key]['macd_line'] > $data[$key]['signal_line']){
                    $data[$key]['status'] = 'BUY';
                } else if($data[$key-1]['macd_line'] > $data[$key-1]['signal_line'] && $data[$key]['macd_line'] < $data[$key]['signal_line']){
                    $data[$key]['status'] = 'SELL';
                } else {
                    $data[$key]['status'] = 'NONE';
                }
            }
        }

        return $data;
    }

    static function calculate($data)
    {
        $data_close = [];
        foreach($data as $row_close){
            array_push($data_close, $row_close['close']);
        }

        $ema12 = MACD::calculateEMA($data_close, 12);
        $ema26 = MACD::calculateEMA($data_close, 26);
        $macd = []; 
        for($i = 0; $i < count($ema12); $i++) {
            $macd_value = round((float)$ema12[$i] - (float)$ema26[$i], 8);
            array_push($macd, (float)$macd_value);
        }

        $ema9 = MACD::calculateEMA($macd, 9);
        
        for($i = 0; $i < count($data); $i++) {
            $ema12_line = $ema12[$i];
            $ema26_line = $ema26[$i];
            $macd_line = $macd[$i];
            $signal_line = $ema9[$i];

            $data[$i]['macd']['ema12'] = $ema12_line;
            $data[$i]['macd']['ema26'] = $ema26_line;
            $data[$i]['macd']['macd_line'] = $macd_line;
            $data[$i]['macd']['signal_line'] = $signal_line;

            if($i != 0){
                if($data[$i-1]['macd']['macd_line'] < $data[$i-1]['macd']['signal_line'] && $data[$i]['macd']['macd_line'] > $data[$i]['macd']['signal_line']){
                    $data[$i]['macd']['status'] = 'BUY';
                } else if($data[$i-1]['macd']['macd_line'] > $data[$i-1]['macd']['signal_line'] && $data[$i]['macd']['macd_line'] < $data[$i]['macd']['signal_line']){
                    $data[$i]['macd']['status'] = 'SELL';
                } else {
                    $data[$i]['macd']['status'] = 'NONE';
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
