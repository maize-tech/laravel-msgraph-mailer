<?php

namespace Maize\MsgraphMail;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Maize\MsgraphMail\Commands\MsgraphMailCommand;

class MsgraphMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-msgraph-mail')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_msgraph_mail_table')
            ->hasCommand(MsgraphMailCommand::class);
    }
}
