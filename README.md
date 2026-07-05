# MOE Laravel Content Workflow

All-in-one **content workflow management** untuk Laravel. Satu paket: state machine, scheduling, versioning, audit log, **dan** WYSIWYG editor. Dibuat oleh **MOE (MindOfEmanizer)**.

> Berhenti nulis ulang logic status, approval, scheduling, dan versioning di setiap project Laravel. Tinggal `composer require` dan jalan.

## Fitur

- **State machine** — status (draft → pending_review → published → archived) + transisi terbatas
- **Scheduling** — jadwalin publish/unpublish/archive di masa depan, otomatis dieksekusi via queue
- **Versioning** — tiap perubahan tersimpan, bisa rollback kapan aja
- **Audit trail** — catat siapa, kapan, dan apa yang berubah (lengkap dengan before/after)
- **WYSIWYG editor** — Tiptap-based editor + upload gambar via image-pipeline
- **Blade directives** — `@moeContentStatus`, `@moeContentCan`
- **Livewire components** — ContentEditor, ContentStatusManager, ContentScheduler, ContentVersionHistory, ContentAuditLog
- **Polymorphic** — pasang di model mana aja (Post, Product, Page, dll)

## Requirements

- PHP `^8.2`
- Laravel `^11 | ^12 | ^13`
- Livewire `^3.0`

## Instalasi

> Package ini **privat** (tidak dipublikasikan di Packagist).

### A. Konsumsi via GitHub (untuk project lain / deploy)

Tambahkan repository VCS ke `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/mindofemanizer/MOE-Laravel-Content-Workflow" }
]
```

```bash
composer require moe/laravel-content-workflow:^1.0
```

Karena repo privat, Composer butuh GitHub Personal Access Token (scope `repo`):

```bash
composer config --global github-oauth.github.com <TOKEN>
```

### B. Pengembangan lokal

Kloning sejajar dengan project, lalu pakai path repository:

```json
"repositories": [
    { "type": "path", "url": "../MOE-Laravel-Content-Workflow" }
]
```

```bash
composer require moe/laravel-content-workflow:@dev
```

### Publish config & migration

```bash
php artisan vendor:publish --tag=moe-content-config
php artisan vendor:publish --tag=moe-content-migrations
php artisan migrate
```

Migration otomatis di-load oleh package, jadi cukup `php artisan migrate` tanpa publish jika tidak perlu mengubahnya.

## Quick Start

### 1. Buat model jadi publishable

```php
use Illuminate\Database\Eloquent\Model;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Traits\HasContentWorkflow;

class Post extends Model implements Publishable
{
    use HasContentWorkflow;

    // Opsional: override field names
    protected ?string $contentStatusField = 'status';
    protected ?string $publishedAtField = 'published_at';
    protected ?string $authorField = 'user_id';
    protected ?string $editorField = 'updated_by';
}
```

### 2. Transisi status dari controller

```php
$post = Post::find(1);

// Via trait
$post->setContentStatus('published');

// Via facade
use MOE\ContentWorkflow\Facades\MoeContent;

MoeContent::transition($post, 'published', 'Approved by editor');
MoeContent::canTransition($post, 'archived'); // true/false
MoeContent::getAvailableTransitions($post); // collection of statuses

// Scheduling
MoeContent::schedule($post, now()->addDays(3), 'publish');

// Versioning
MoeContent::createVersion($post, 'Before major edit');
MoeContent::restoreVersion($post, 2);
MoeContent::getVersions($post); // all versions

// Audit
MoeContent::getAuditTrail($post); // all audit records
```

### 3. Livewire components di view

```blade
{{-- WYSIWYG editor --}}
<livewire:moe-content-editor :content="$post" field="body" />

{{-- Status badge + transition buttons --}}
<livewire:moe-content-status-manager :content="$post" />

{{-- Schedule publish/unpublish --}}
<livewire:moe-content-scheduler :content="$post" />

{{-- Version history with restore & diff --}}
<livewire:moe-content-versions :content="$post" />

{{-- Audit trail --}}
<livewire:moe-content-audit-log :content="$post" />
```

### 4. Blade directives

```blade
{{-- Status badge --}}
{!! \MOE\ContentWorkflow\Facades\MoeContent::renderStatus($post) !!}

{{-- Conditionally show content --}}
@moeContentCan('transition:published', $post)
    <button>Publish</button>
@endmoeContentCan
```

## Konfigurasi

File `config/content-workflow.php`:

```php
return [
    'statuses' => [
        'draft' => [
            'label' => 'Draft',
            'color' => 'gray',
        ],
        'pending_review' => [
            'label' => 'Pending Review',
            'color' => 'yellow',
        ],
        'published' => [
            'label' => 'Published',
            'color' => 'green',
        ],
        'archived' => [
            'label' => 'Archived',
            'color' => 'red',
        ],
    ],

    'transitions' => [
        'draft' => ['pending_review', 'published', 'archived'],
        'pending_review' => ['draft', 'published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ],

    'scheduling' => [
        'enabled' => true,
        'table' => 'content_schedules',
    ],

    'versioning' => [
        'enabled' => true,
        'max_versions' => 50,
    ],

    'audit' => [
        'enabled' => true,
        'log_retention_days' => 365,
    ],
];
```

## Artisan Commands

```bash
# Proses semua jadwal yang sudah due
php artisan moe:publish-workflow

# Jadwal manual dari CLI (contoh)
php artisan moe:schedule-content publish 1 2 3 --type=App\\Models\\Post --at="now+2hours"
```

## Integrasi Package MOE

Package ini kompatibel dengan package MOE lainnya:

| Package | Keterangan |
|---|---|
| `moe/laravel-identifiers` | Untuk public ID & document number konten (suggest) |
| `moe/laravel-image-pipeline` | Untuk upload & transformasi gambar di WYSIWYG editor (suggest) |

## Testing

```bash
composer test
```

## License

MIT © MOE (MindOfEmanizer)
