<?php

declare(strict_types=1);

namespace Simtabi\SIS\Snapshot;

use Simtabi\SIS\Contract\Snapshot;
use Simtabi\SIS\Enums\LifecycleState;

final readonly class AttachSubjectSnapshot implements Snapshot
{
    public function __construct(
        public LifecycleState $currentState,
        public bool $subjectAlreadyNamed,
    ) {}
}
