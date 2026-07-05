<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \MOE\ContentWorkflow\ContentWorkflowServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'MoeContent' => \MOE\ContentWorkflow\Facades\MoeContent::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('content-workflow.statuses', [
            'draft' => ['label' => 'Draft', 'color' => 'gray', 'icon' => 'pencil', 'description' => ''],
            'pending_review' => ['label' => 'Pending Review', 'color' => 'yellow', 'icon' => 'clock', 'description' => ''],
            'published' => ['label' => 'Published', 'color' => 'green', 'icon' => 'check', 'description' => ''],
            'archived' => ['label' => 'Archived', 'color' => 'red', 'icon' => 'archive', 'description' => ''],
        ]);

        $app['config']->set('content-workflow.transitions', [
            'draft' => ['pending_review', 'published', 'archived'],
            'pending_review' => ['draft', 'published', 'archived'],
            'published' => ['draft', 'archived'],
            'archived' => ['draft'],
        ]);

        $app['config']->set('content-workflow.versioning', [
            'enabled' => true,
            'max_versions' => 50,
            'cleanup_old' => true,
        ]);

        $app['config']->set('content-workflow.audit', [
            'enabled' => true,
            'log_retention_days' => 365,
        ]);

        $app['config']->set('content-workflow.scheduling', [
            'enabled' => true,
            'check_interval' => 'every_minute',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue.default', 'sync');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
