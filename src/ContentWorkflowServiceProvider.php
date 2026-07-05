<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use MOE\ContentWorkflow\Console\PublishWorkflowCommand;
use MOE\ContentWorkflow\Console\ScheduleContentCommand;
use MOE\ContentWorkflow\Services\AuditService;
use MOE\ContentWorkflow\Services\ScheduleService;
use MOE\ContentWorkflow\Services\StateMachineService;
use MOE\ContentWorkflow\Services\VersioningService;

class ContentWorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/content-workflow.php',
            'content-workflow'
        );

        $this->app->singleton(ContentWorkflowManager::class);
        $this->app->alias(ContentWorkflowManager::class, 'moe.content');

        $this->app->singleton(StateMachineService::class);
        $this->app->singleton(ScheduleService::class);
        $this->app->singleton(VersioningService::class);
        $this->app->singleton(AuditService::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerBladeDirectives();
        $this->registerLivewireComponents();
        $this->registerViews();
    }

    protected function registerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/content-workflow.php' => config_path('content-workflow.php'),
        ], 'moe-content-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'moe-content-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/moe-content'),
        ], 'moe-content-views');
    }

    protected function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PublishWorkflowCommand::class,
            ScheduleContentCommand::class,
        ]);
    }

    protected function registerBladeDirectives(): void
    {
        $blade = $this->app->make('view')->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('moeContentStatus', function ($expression) {
            return "<?php echo app('moe.content')->renderStatus($expression); ?>";
        });

        $blade->directive('moeContentCan', function ($expression) {
            return "<?php if (app('moe.content')->can($expression)): ?>";
        });

        $blade->directive('endmoeContentCan', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('moeImage', function ($expression) {
            return "<?php echo app('moe.image')->render($expression); ?>";
        });
    }

    protected function registerLivewireComponents(): void
    {
        if (!class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('moe-content-editor', \MOE\ContentWorkflow\Http\Livewire\ContentEditor::class);
        Livewire::component('moe-content-status-manager', \MOE\ContentWorkflow\Http\Livewire\ContentStatusManager::class);
        Livewire::component('moe-content-scheduler', \MOE\ContentWorkflow\Http\Livewire\ContentScheduler::class);
        Livewire::component('moe-content-versions', \MOE\ContentWorkflow\Http\Livewire\ContentVersionHistory::class);
        Livewire::component('moe-content-audit-log', \MOE\ContentWorkflow\Http\Livewire\ContentAuditLog::class);
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moe-content');
    }
}
