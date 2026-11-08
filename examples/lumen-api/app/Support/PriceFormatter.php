<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Legacy pattern: a singleton that captures the Request in its constructor.
 * Under a long-lived worker the first request's locale is used forever.
 */
class PriceFormatter
{
    private string $locale;

    public function __construct(Request $request)
    {
        $this->locale = str_starts_with((string) $request->header('Accept-Language'), 'en') ? 'en' : 'pt_BR';
    }

    public function format(int $cents): string
    {
        $value = $cents / 100;

        return $this->locale === 'en'
            ? '$'.number_format($value, 2, '.', ',')
            : 'R$ '.number_format($value, 2, ',', '.');
    }

    public function locale(): string
    {
        return $this->locale;
    }
}
