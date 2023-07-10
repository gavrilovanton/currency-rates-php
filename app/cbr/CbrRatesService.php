<?php

namespace App\cbr;

use App\Application;
use App\Rate;
use App\RatesServiceInterface;

class CbrRatesService implements RatesServiceInterface
{
    public static function getRate(string $code, string $baseCurrency, string $date): Rate|null
    {

        $requestedDayRate = CbrCurrencyList::forDate($date)->getRate($code, $baseCurrency);

        if($requestedDayRate) {

            //calculating next date
            $previousDate = date("d/m/Y", strtotime(str_replace('/', '-', $date)) - 60*60*24);

            //requesting the rate for the previous day
            $previousDayRate = CbrCurrencyList::forDate($previousDate)->getRate($code, $baseCurrency);

            $diff = 0;
            if($previousDayRate) {
                $diff = $requestedDayRate->value - $previousDayRate->value; //calculating difference
            }

            $rate = new Rate();

            $rate->date = str_replace('/', '.', $date); //european format looks prettier in json
            $rate->name = $requestedDayRate->name;
            $rate->code = $requestedDayRate->charCode;
            $rate->value = $requestedDayRate->value;
            $rate->diff = $diff;

            return $rate;
        }

        return null;
    }
}