<?php

declare(strict_types=1);

namespace Simtabi\SIS\Profile;

/**
 * The vocabulary that drives alias derivation from a legal name — SIM-STD-0001:2026 §5.2. Legal-form
 * suffixes and generic words are stripped so `Acme Corp` and `Acme` derive the same code; vowels are the
 * skeleton the derivation may drop; padding fills a code short of the minimum length.
 */
final readonly class AliasDerivation
{
    /**
     * @param  list<string>  $legalSuffixes  company-form suffixes stripped from the tail (LLC, INC, …)
     * @param  list<string>  $genericWords  words re-admitted only to break a tie (GROUP, LABS, …)
     * @param  list<string>  $vowels  the letters the consonant-skeleton derivation may drop
     */
    public function __construct(
        public array $legalSuffixes,
        public array $genericWords,
        public string $padding,
        public array $vowels,
        public int $min,
        public int $max,
    ) {}
}
