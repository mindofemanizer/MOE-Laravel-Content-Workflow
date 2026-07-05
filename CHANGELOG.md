# Changelog

## v1.0.0 (2026-07-05)

- Initial release
- State machine with configurable statuses and transitions
- Content scheduling with queue support
- Versioning with restore and diff capabilities
- Audit trail with IP, user agent, and full snapshots
- WYSIWYG editor (Tiptap) with image upload via moe/laravel-image-pipeline
- Livewire components: ContentEditor, ContentStatusManager, ContentScheduler, ContentVersionHistory, ContentAuditLog
- Blade directives: @moeContentStatus, @moeContentCan
- Artisan commands: moe:publish-workflow, moe:schedule-content
- PSR-12 compliant, 29 unit tests
