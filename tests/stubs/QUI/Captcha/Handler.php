<?php

namespace QUI\Captcha;

if (!class_exists(Handler::class)) {
    class Handler
    {
        public static function isResponseValid(string $response): bool
        {
            return true;
        }
    }
}
