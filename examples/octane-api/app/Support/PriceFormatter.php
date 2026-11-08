<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formats prices stored in cents.
 *
 * Stateless on purpose: the locale is an argument, never captured from the
 * Request, so one instance can serve every request in a long-lived worker.
 */
final class PriceFormatter
{
    public const PT_BR = 'pt_BR';

    public const EN = 'en';

    public function format(int $cents, string $locale): string
    {
        $value = $cents / 100;

        return $locale === self::EN
            ? '$'.number_format($value, 2, '.', ',')
            : 'R$ '.number_format($value, 2, ',', '.');
    }

    public static function localeFrom(?string $acceptLanguage): string
    {
        return str_starts_with((string) $acceptLanguage, 'en') ? self::EN : self::PT_BR;
    }
}
