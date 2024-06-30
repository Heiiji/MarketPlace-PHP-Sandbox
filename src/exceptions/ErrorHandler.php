<?php

namespace exceptions;
class ErrorHandler
{
    public static function handleError(
        int    $errno,
        string $errstr,
        string $errfile,
        int    $errline,
    ): void
    {
        throw new ErrorException($errstr, $errno, 1, $errfile, $errline);
    }

    public static function handleException(Throwable $exception): void
    {
        http_response_code(500);
        echo json_encode([
            "code" => $exception->getCode(),
            "message" => $exception->getMessage(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
        ]);
    }
}