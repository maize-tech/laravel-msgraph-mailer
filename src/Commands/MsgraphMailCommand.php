<?php

namespace Maize\MsgraphMail\Commands;

use Illuminate\Console\Command;

class MsgraphMailCommand extends Command
{
    public $signature = 'laravel-msgraph-mail';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
