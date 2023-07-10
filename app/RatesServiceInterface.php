<?php

namespace App;

interface RatesServiceInterface
{
    public static function getRate(string $code, string $baseCurrency, string $date): Rate|null;
}