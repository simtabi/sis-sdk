<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Policy;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Identifier\TakenAliases;
use Simtabi\SIS\Sis;

/**
 * Alias derivation, driven through the engine instance (which builds the policy from the SIM profile's
 * derivation vocabulary and reserved list).
 */
final class AliasPolicyTest extends TestCase
{
    public function test_derives_the_head_plus_distinctive_tail(): void
    {
        $sis = new Sis;

        self::assertSame('ADIQ', $sis->aliasCandidates('AdelsaIQ LLC')->all()[0]);
        self::assertSame('ACME', $sis->aliasCandidates('Acme Corp')->all()[0]);
    }

    public function test_excludes_reserved_aliases(): void
    {
        $candidates = (new Sis)->aliasCandidates('Simtabi LLC')->all();

        self::assertNotContains('SIMT', $candidates, 'SIMT is reserved (§5.3) and must never be offered');
        self::assertContains('SIBI', $candidates);
    }

    public function test_every_candidate_is_valid_and_unreserved(): void
    {
        $sis = new Sis;

        foreach (['AdelsaIQ LLC', 'Acme Corp', 'Café Solutions GmbH', 'Zed & Partners LLP', 'Northwind Traders'] as $name) {
            $candidates = $sis->aliasCandidates($name)->all();
            self::assertNotEmpty($candidates, $name);

            foreach ($candidates as $candidate) {
                self::assertMatchesRegularExpression('/^[A-Z][A-Z0-9]{3,5}$/', $candidate);
                self::assertFalse($sis->isReservedAlias($candidate));
            }

            self::assertSame($candidates, array_values(array_unique($candidates)), "candidates for {$name} are unique");
        }
    }

    public function test_choose_skips_taken_candidates(): void
    {
        $candidates = (new Sis)->aliasCandidates('Acme Corp');
        $taken = new TakenAliases(['ACME']);

        self::assertNotSame('ACME', $candidates->choose($taken)->value);
    }
}
