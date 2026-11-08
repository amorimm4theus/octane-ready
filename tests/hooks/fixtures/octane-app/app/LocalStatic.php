<?php

final class LocalStatic
{
    public function lookup(string $key): string
    {
        static $seen = [];

        return $seen[$key] ??= strtoupper($key);
    }
}
