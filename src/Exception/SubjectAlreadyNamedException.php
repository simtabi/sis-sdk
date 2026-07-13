<?php

declare(strict_types=1);

namespace Simtabi\SIS\Exception;

use Simtabi\SIS\Spec;

/**
 * One thing, one identifier — the thesis of the whole specification. The subject a command is trying
 * to attach is already named by another identifier.
 */
final class SubjectAlreadyNamedException extends SisConflictException
{
    protected const string SPEC_CLAUSE = Spec::DOCUMENT . ' §9';

    public static function of(string $subjectType, string $subjectId, ?string $by = null): self
    {
        return new self(
            sprintf(
                'Subject %s:%s is already named%s (' . Spec::DOCUMENT . ' §9). One thing, one identifier.',
                $subjectType,
                $subjectId,
                $by !== null ? ' by ' . $by : '',
            ),
            ['operation' => 'attach-subject', 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'named_by' => $by],
        );
    }
}
