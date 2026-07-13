<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Grammar;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Grammar\IdentifierGrammar;
use Simtabi\SIS\Profile\Sim\SimProfile;

/**
 * The oracle: the grammar compiled from the SIM profile must equal, byte for byte, the expected FORM_G /
 * FORM_S literals. These strings are the specification's grammar SHAPE (§2); the class token is `[A-Z]{3,4}`
 * (three- or four-letter codes). If this test fails, the profile-driven compiler has drifted from the
 * specified grammar.
 */
final class GrammarOracleTest extends TestCase
{
    private const string FORM_G = '/^SIM-([A-Z]{3,4})-(\d{6,9})-([0-9A-Z]{2})$/';

    private const string FORM_S = '/^SIM-([A-Z]{3,4})-([A-Z][A-Z0-9]{3,5})-(\d{6,9})-([0-9A-Z]{2})$/';

    public function test_compiled_sim_grammar_equals_the_frozen_literals(): void
    {
        $grammar = new IdentifierGrammar(SimProfile::create());

        self::assertSame(self::FORM_G, $grammar->formG());
        self::assertSame(self::FORM_S, $grammar->formS());
    }
}
