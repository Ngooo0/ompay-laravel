<?php

namespace App\Utils;

use Illuminate\Support\Str;

class GenererUuid
{
    public static function uuid(): string
    {
        return (string) Str::uuid();
    }
}
