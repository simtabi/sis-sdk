<?php

declare(strict_types=1);

namespace Simtabi\SIS;

/**
 * The specification this SDK implements — a single source of truth for the document identifier and its
 * edition, so the ~40 clause citations scattered across the exception messages and the engine's own
 * constants never drift from one another.
 *
 * The document number and edition are fixed by the specification; only the clause numbers (`§n`) vary per
 * citation and stay inline where they are cited.
 */
final class Spec
{
    /** The controlling specification document. */
    public const string DOCUMENT = 'SIM-STD-0001:2026';

    /** The current edition of that specification. */
    public const string EDITION = 'SIS/1';
}
