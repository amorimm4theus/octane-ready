<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

arch()->preset()->php();

arch()->preset()->security();

arch('every class declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('controllers stay thin: no direct database access')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('actions are final and expose handle()')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toHaveMethod('handle');

it('has no static properties in App: they survive between requests under Octane', function () {
    $offenders = collect(File::allFiles(app_path()))
        ->map(fn (SplFileInfo $file) => 'App\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->filter(fn (string $class) => class_exists($class))
        ->flatMap(fn (string $class) => collect((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_STATIC))
            ->filter(fn (ReflectionProperty $p) => $p->getDeclaringClass()->getName() === $class)
            ->map(fn (ReflectionProperty $p) => "{$class}::\${$p->getName()}"))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
