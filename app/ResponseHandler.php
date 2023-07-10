<?php

namespace App;
class ResponseHandler
{
    public int $status;
    public array $response;
    private function __construct($status, $response)
    {
        $this->status = $status;
        $this->response = $response;
    }
    public static function ok(object|array|string $data): ResponseHandler
    {
        return new self(200, [
            "status" => 200,
            "data" => $data
        ]);
    }

    public static function accepted(string $message): ResponseHandler
    {
        return new self(202, [
            "status" => 202,
            "message" => $message
        ]);
    }

    public static function error(int $code, string $message): ResponseHandler
    {
        return new self($code, [
            "status" => $code,
            "message" => $message
        ]);
    }

    public function render(): void
    {
        header("Status: $this->status");
        header("Content-type: application/json; charset=utf-8");

        $response = json_encode($this->response);

        Application::log()->info("Response: ". substr($response, 0, 500));

        exit($response);
    }
}