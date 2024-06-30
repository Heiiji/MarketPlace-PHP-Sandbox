<?php

namespace helpers;
use exceptions\InvalidSignatureException;
use exceptions\TokenExpiredException;

class JWTCodec
{
    public function __construct(private string $key)
    {

    }

    public function encode(array $payload): string
    {
        $header = json_encode([
            "typ" => "JWT",
            "alg" => "HS256"
        ]);
        $header = $this->base64UrlEncode($header);

        $payload = json_encode($payload);
        $payload = $this->base64UrlEncode($payload);

        $sign = hash_hmac('sha256', $header . "." . $payload, $this->key, true);
        $sign = base64_encode($sign);

        return $header . "." . $payload . "." . $sign;
    }

    public function decode(string $token): array
    {
        if (preg_match("/^(?<header>.+)\.(?<payload>.+)\.(?<sign>.+)$/", $token, $matches) !== 1) {
            throw new InvalidArgumentException("Invalid JWT format");
        }

        $sign = hash_hmac('sha256', $matches["header"] . "." . $matches["payload"], $this->key, true);
        $token_sign = $this->base64UrlDecode($matches["sign"]);

        if (!hash_equals($sign, $token_sign)) {
            throw new InvalidSignatureException();
        }

        $payload = json_decode($this->base64UrlDecode($matches["payload"]), true);

        if ($payload["exp"] < time()) {
            throw new TokenExpiredException();
        }

        return $payload;
    }

    private function base64UrlEncode(string $text): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private function base64UrlDecode(string $text): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $text));
    }
}