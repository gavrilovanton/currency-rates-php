<?php

namespace Tests;

use App\Application;
use App\cbr\CbrRatesService;
use PHPUnit\Framework\TestCase;

//docker container with Redis must be running
class AppIntegrationTests extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        //test config is different
        $this->app = Application::init([
            "REDIS_HOST" => "localhost",
            "REDIS_PORT" => 6379
        ]);
    }

    public function test_get_rates_USD()
    {

        $result = $this->app->ratesController((object)[
            "currency" => "USD",
            "date" => "09/06/2023"
        ]);

        $this->assertEquals(200, $result->status);
        $this->assertEquals(82.093, $result->response['data']->value);
        $this->assertEquals(0.6349000000000018, $result->response['data']->diff);
    }

    public function test_get_rate_for_EUR()
    {
        $result = $this->app->ratesController((object)[
            "currency" => "USD",
            "date" => "09/06/2023",
            "baseCurrency" => "EUR"
        ]);

        $this->assertEquals(200, $result->status);
        $this->assertEquals(0.9324734006603975, $result->response['data']->value);
    }

    public function test_get_rate_for_nonexistent_currency()
    {
        $result = $this->app->ratesController((object)[
            "currency" => "TTT",
            "date" => "09/06/2023"
        ]);

        $this->assertEquals(404, $result->status);
    }

    public function test_get_rate_for_future_date()
    {
        $result = $this->app->ratesController((object)[
            "currency" => "USD",
            "date" => "09/06/2050"
        ]);

        $this->assertEquals(404, $result->status);
    }
}
