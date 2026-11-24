<?php

namespace App\Services;

use RuntimeException;

class SupportSensitiveDataRedactor
{
    public function redact(string $message): string
    {
        $patterns = [
            '/(?<![0-9٠-٩۰-۹])(?:[0-9٠-٩۰-۹][ -]?){12,18}[0-9٠-٩۰-۹](?![0-9٠-٩۰-۹])/u',
            '/(?<![A-Za-z0-9])\+?(?:[0-9٠-٩۰-۹][ .()\-]*){6,14}[0-9٠-٩۰-۹](?![A-Za-z0-9])/u',
        ];

        foreach ($patterns as $pattern) {
            $redacted = preg_replace($pattern, '********', $message);

            if ($redacted === null) {
                throw new RuntimeException('Customer message could not be safely redacted.');
            }

            $message = $redacted;
        }

        return $message;
    }
}
