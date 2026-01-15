<?php

namespace Maize\MsgraphMail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Maize\MsgraphMail\MsgraphMail
 */
class MsgraphMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Maize\MsgraphMail\MsgraphMail::class;
    }
}
