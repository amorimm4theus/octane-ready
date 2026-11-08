<?php

final class CurrentActor
{
    private static ?self $memo = null;

    private static ?int $requestId = null;

    public static function resolve(object $request): self
    {
        if (self::$memo !== null && self::$requestId === spl_object_id($request)) {
            return self::$memo;
        }

        self::$requestId = spl_object_id($request);

        return self::$memo = new self;
    }
}
