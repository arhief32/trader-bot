<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MACD extends Model
{
    use HasFactory;
    public static function processMacdData($dataStore, $scrip, $all = false, $sm = 12, $mid = 26, $sig = 9)
    {
        /*Process MA data as follows
         1. Loop only once to calculate everything
         2. get 26 day EMA for today and yesterday
         3. get 12 day EMA for today and yesterday
         4. get 9 day EMA for today and yesterday
         5. get today's Volume
         6. get average of 5 day volume
         */

        /* Initialize vars */
        $j = 0;
        $long = 200;

        $emaSm = 0;
        $emaMid = 0;
        $emaLong = 0;
        $emaSig = 0;

        /* Calculate EMA multipliers */
        $multSm = 2 / ($sm + 1);
        $multMid = 2 / ($mid + 1);
        $multLong = 2 / ($long + 1);
        $multSig = 2 / ($sig + 1);

        $totalSm = 0;
        $totalMid = 0;
        $totalLong = 0;
        $totalSig = 0;
        $totalV = 0;

        $results = array();

        /* Check whether we have enough data to calculate MA crossover */
        $datacount = count($dataStore);
        $required = $mid * 6;
        if ($datacount < $required) {
            /* Unable to proceed return zero */
            error_log("Not enough data(count = $datacount, required = $required): $scrip");
            return $results;
        }

        /* MACD calculation must be done with single pass. Iterate once and calculate
         everything.
         */
        for ($i = 0; $i < $datacount; $i++) {

            $curr_data = $dataStore[$i];
            $curr_c = $curr_data["c"];
            $curr_v = $curr_data["v"];
            $curr_d = $curr_data["d"];

            /**************************************************************************/
            if ($i < $sm) {
                $totalSm += $curr_c;
            }
            if ($i == $sm - 1) {
                $emaSm = $totalSm / $sm;
                unset($totalSm);
            }
            if ($i >= $sm) {
                $emaSm = (($curr_c - $emaSm) * $multSm) + $emaSm;
            }
            /**************************************************************************/
            if ($i < $mid) {
                $totalMid += $curr_c;
            }
            if ($i == $mid - 1) {
                $emaMid = $totalMid / $mid;
                unset($totalMid);
            }
            if ($i >= $mid) {
                $emaMid = (($curr_c - $emaMid) * $multMid) + $emaMid;
            }
            /**************************************************************************/
            if ($i < $long) {
                $totalLong += $curr_c;
            }
            if ($i == $long - 1) {
                $emaLong = $totalLong / $long;
                unset($totalLong);
            }
            if ($i >= $long) {
                $emaLong = (($curr_c - $emaLong) * $multLong) + $emaLong;
            }
            /**************************************************************************/
            if ($emaSm && $emaMid) {
                $curr_macd = round($emaSm - $emaMid, 2);

                /* Calculate signal */
                if ($j < $sig) {
                    $totalSig += $curr_macd;
                }
                if ($j == $sig - 1) {
                    $emaSig = $totalSig / $sig;
                    unset($totalSig);
                }
                if ($j >= $sig) {
                    $emaSig = (($curr_macd - $emaSig) * $multSig) + $emaSig;
                }

                /* Add to results */
                if ($emaSig) {
                    if ($all) {
                        $results[$curr_d] = array("m" => $curr_macd
                            , "s" => round($emaSig, 2)
                            , "c" => $curr_c
                            , "v" => $curr_v
                        );
                    }
                    else {
                        if ($i >= $datacount - 2) {
                            $results[] = array("macd" => $curr_macd
                                , "signal" => round($emaSig, 2)
                                , "close" => $curr_c
                                , "volume" => $curr_v
                                , "long" => round($emaLong)
                                , "date" => $curr_d
                            );
                        }
                    }
                }
                /* counter for macd values */
                $j += 1;
            }
        /**************************************************************************/
        }

        return $results;    
    }
}
