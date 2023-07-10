<?php

declare(strict_types = 1);

namespace App;

use App\cbr\CbrRatesService;
use Predis\Client;
use Exception;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Application
{
    private static Client $cache;
    private static Logger $log;

    public static function init(array $config): Application
    {
        $app = new self();
        //configuring log
        $app::$log = new Logger('App');
        if(!empty($config['LOG_PATH'])){
            $app::$log->pushHandler(new StreamHandler($config['LOG_PATH'], Level::Debug));
        }
        //creating client for Redis and checking connection
        try {
            $app::$cache = new Client("tcp://". $config['REDIS_HOST'].":".$config['REDIS_PORT']."?read_write_timeout=-1");
            $app::$cache->connect();
        } catch (Exception $e) {
            $app::$log->error($e);
            ResponseHandler::error(500, "Couldn't connect to Redis")->render();
        }
        return $app;
    }

    public function ratesController($request): ResponseHandler
    {
        //validating that currency is not empty
        if(empty($request->currency)) {
            return ResponseHandler::error(400, "Bad Request");
        }

        //setting base currency, if it's empty
        if(empty($request->baseCurrency)) {
            $request->baseCurrency = "RUB";
        }

        //if date is empty setting it to now
        if(empty($request->date)) {
            $request->date = date("d/m/Y");
        }

        Application::log()->info("Request:" . json_encode($request));

        //pushing request to the queue if 'days' parameter is present
        if(!empty($request->days)) {

            Application::log()->info("Requested report for $request->days days");

            //hashing request to get a unique ID
            $hash = md5($request->currency.$request->baseCurrency.$request->days);
            //checking whether report is ready
            $report = Application::cache()->hget("reports", $hash);

            //if not pushing request to the queue
            if (!$report) {
                Application::log()->info("Report wasn't found in cache pushing request to the queue");
                Application::cache()->rpush("report_queue", json_encode($request));
                return ResponseHandler::accepted("Report for $request->days days will be ready in a couple minutes. Refresh the page.");
            } else {
                Application::log()->info("Report was found in cache, returning it to the client");
                return ResponseHandler::ok(json_decode($report));
            }
        }

        Application::log()->info("Requested rate for $request->currency on $request->date");
        //requesting currency rate if 'days' are absent
        try {
            $rate = CbrRatesService::getRate(
                $request->currency,
                $request->baseCurrency,
                $request->date
            );
        } catch(Exception $e) {
            Application::log()->error($e);
            return ResponseHandler::error(500, "Sorry. Something went wrong.");
        }

        if($rate) {
            return ResponseHandler::ok($rate);
        } else {
            return ResponseHandler::error(404, "Currency wasn't listed by Bank of Russia on this date");
        }
    }

    public function reportsWorker(): void
    {
        Application::log()->info("Starting reposts worker");

        try {
            //preparing reports from the queue
            while (true) {

                $request = Application::cache()->blpop("report_queue", 30);

                if(!$request) {
                    Application::log()->info("Reports worker: Waiting...");
                    continue;
                }

                Application::log()->info("Reports worker: There is a new request!");

                $requestObj = json_decode($request[1]);

                $hash = md5($requestObj->currency . $requestObj->baseCurrency . $requestObj->days);

                if(Application::cache()->hGet("reports", $hash)) {
                    Application::log()->info("Report $hash is already created");
                    continue;
                }

                $timeStamp = time();
                $days = $requestObj->days;
                $report = [];

                while ($days > 0) {
                    $rate = CbrRatesService::getRate(
                        $requestObj->currency,
                        $requestObj->baseCurrency,
                        date("d/m/Y", $timeStamp)
                    );

                    if ($rate) $report[] = $rate;

                    $timeStamp -= 60 * 60 * 24; //calculating previous day
                    $days -= 1;
                }

                Application::cache()->hSet("reports", $hash, json_encode($report));

                Application::log()->info("Report $hash is ready");
            }
        } catch (Exception $e) {
            Application::log()->error($e);
        }
    }

    public static function cache(): Client
    {
        return static::$cache;
    }

    public static function log(): Logger
    {
        return static::$log;
    }
}