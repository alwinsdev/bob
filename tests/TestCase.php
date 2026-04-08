<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\Support\StableFilesystem;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewPath = storage_path('framework/testing/views/'.$this->compiledViewDirectorySuffix());
        File::ensureDirectoryExists($compiledViewPath);

        config()->set('view.compiled', $compiledViewPath);

        $filesystem = new StableFilesystem;
        $this->app->instance('files', $filesystem);
        $this->app->instance(Filesystem::class, $filesystem);

        $this->app->forgetInstance('blade.compiler');
        $this->app->forgetInstance('view.engine.resolver');
        View::flushFinderCache();
    }

    private function compiledViewDirectorySuffix(): string
    {
        $testName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $this->name()) ?? 'test';
        $className = preg_replace('/[^A-Za-z0-9_-]+/', '_', static::class) ?? 'case';
        $uniqueSuffix = preg_replace('/[^A-Za-z0-9_-]+/', '_', uniqid('', true)) ?? 'run';

        return "{$className}_{$testName}_{$uniqueSuffix}";
    }
}
