<?php

namespace App\cbr;

use App\Application;
use SimpleXMLElement;
use Exception;

class CbrCurrencyList extends SimpleXMLElement
{
    public static function forDate($date): CbrCurrencyList|false
    {
        try{
            $cache = Application::cache();
            $strData = $cache->hget("cbr_currency_lists", $date);
            if(!$strData) {
                $strData = file_get_contents("https://cbr.ru/scripts/XML_daily_eng.asp?date_req=$date");
                $cache->hset("cbr_currency_lists", $date, $strData);
            }
            return new self($strData);
        } catch(Exception $e) {
            Application::log()->error($e->getTraceAsString());
            return false;
        }
    }

    public function getCurrency(string $code): SimpleXMLElement|null
    {
        $arCurrency = $this->xpath("//Valute[CharCode='$code']");
        if(!empty($arCurrency[0])) {
            return $arCurrency[0];
        } else {
            return null;
        }
    }

    public function getRate(string $code, string $baseCurrencyCode): CbrRate|null
    {
        $requestedCurrency = $this->getCurrency($code);

        if($requestedCurrency) {

            //format currency rate because CBR has commas instead of periods
            //also dividing it with the nominal to get the real value
            $value = floatval(str_replace(",", ".", strval($requestedCurrency->Value))) / intval($requestedCurrency->Nominal);

            //calculating the cross rate if the base currency isn't ruble
            if($baseCurrencyCode !== "RUB") {
                $baseCurrency = $this->getCurrency($baseCurrencyCode);
                if($baseCurrency) {
                    $baseCurrencyValue = floatval(str_replace(",", ".", strval($baseCurrency->Value))) / intval($baseCurrency->Nominal);
                    $value = $value / $baseCurrencyValue;
                } else {
                    return null;
                }
            }

            $cbrRate = new CbrRate();

            $cbrRate->charCode = strval($requestedCurrency ->CharCode);
            $cbrRate->name = strval($requestedCurrency ->Name);
            $cbrRate->value = $value;

            return $cbrRate;

        } else {
            return null;
        }
    }
}