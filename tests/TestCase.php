<?php

namespace Tomoki\SoftDeletesValue\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineDatabaseMigrations()
    {
        Schema::create('strict_models', function (Blueprint $table) {
            $table->id();
            $table->timestamp('deleted_at')->default('9999-12-31 23:59:59');
            $table->timestamps();
        });

        Schema::create('nullable_models', function (Blueprint $table) {
            $table->id();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [];
    }
}
