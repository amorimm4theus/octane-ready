<?php

declare(strict_types=1);

use App\Support\PriceFormatter;

it('formats cents in Brazilian real', function () {
    expect((new PriceFormatter)->format(123456, PriceFormatter::PT_BR))->toBe('R$ 1.234,56');
});

it('formats cents in US dollars', function () {
    expect((new PriceFormatter)->format(123456, PriceFormatter::EN))->toBe('$1,234.56');
});

it('picks the locale from Accept-Language', function (?string $header, string $locale) {
    expect(PriceFormatter::localeFrom($header))->toBe($locale);
})->with([
    ['en-US,en;q=0.9', PriceFormatter::EN],
    ['pt-BR', PriceFormatter::PT_BR],
    [null, PriceFormatter::PT_BR],
]);
