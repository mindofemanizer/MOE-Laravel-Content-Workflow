<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MOE\ContentWorkflow\Contracts\Publishable;

class ContentUnpublished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Publishable $content,
    ) {
    }
}
