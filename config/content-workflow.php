<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Statuses
    |--------------------------------------------------------------------------
    |
    | Define the default workflow statuses for your content.
    | You can add custom statuses as needed.
    |
    */
    'statuses' => [
        'draft' => [
            'label' => 'Draft',
            'color' => 'gray',
            'icon' => 'pencil',
            'description' => 'Content is being worked on',
        ],
        'pending_review' => [
            'label' => 'Pending Review',
            'color' => 'yellow',
            'icon' => 'clock',
            'description' => 'Waiting for approval',
        ],
        'published' => [
            'label' => 'Published',
            'color' => 'green',
            'icon' => 'check',
            'description' => 'Content is live',
        ],
        'archived' => [
            'label' => 'Archived',
            'color' => 'red',
            'icon' => 'archive',
            'description' => 'No longer active',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Transitions
    |--------------------------------------------------------------------------
    |
    | Define allowed status transitions.
    | Format: 'from_status' => ['allowed_to_statuses']
    |
    */
    'transitions' => [
        'draft' => ['pending_review', 'published', 'archived'],
        'pending_review' => ['draft', 'published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Configure content scheduling settings.
    |
    */
    'scheduling' => [
        'enabled' => true,
        'table' => 'content_schedules',
        'check_interval' => 'every_minute', // every_minute, every_five_minutes, every_hour
        'timezone' => config('app.timezone', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Versioning
    |--------------------------------------------------------------------------
    |
    | Configure content versioning settings.
    |
    */
    'versioning' => [
        'enabled' => true,
        'table' => 'content_versions',
        'max_versions' => 50, // Maximum versions to keep per content
        'cleanup_old' => true, // Auto delete old versions beyond max_versions
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure audit logging for content changes.
    |
    */
    'audit' => [
        'enabled' => true,
        'table' => 'content_audits',
        'log_fields' => ['status', 'title', 'content', 'published_at'],
        'log_retention_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configure caching for content status and metadata.
    |
    */
    'cache' => [
        'enabled' => true,
        'prefix' => 'moe_content_',
        'ttl' => 3600, // 1 hour
        'store' => config('cache.default', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for background jobs.
    |
    */
    'queue' => [
        'enabled' => true,
        'connection' => config('queue.default', 'sync'),
        'queue' => 'content-workflow',
        'retry_after' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the User model class for author/editor lookups.
    | Defaults to the auth provider's model.
    |
    */
    'user_model' => null, // null = auto-detect from auth.providers.users.model

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding
    |--------------------------------------------------------------------------
    |
    | Configure route model binding settings.
    |
    */
    'route_binding' => [
        'enabled' => true,
        'key' => 'ulid', // ulid, slug, id
        'with_trashed' => false,
    ],
];
