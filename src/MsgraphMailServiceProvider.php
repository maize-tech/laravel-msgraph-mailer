<?php

namespace Maize\MsgraphMail;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MsgraphMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-msgraph-mail')
            ->hasConfigFile();
    }
}
