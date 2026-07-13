<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Enums;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Enums\Environment;
use ValueError;

/**
 * The `Environment` enum resolves the six supported deployment environments from any of their accepted
 * spellings, case-insensitively, and normalises back to a canonical three-letter code.
 */
final class EnvironmentTest extends TestCase
{
    public function test_codes_are_the_six_canonical_three_letter_values_in_case_order(): void
    {
        self::assertSame(['TST', 'DEV', 'SPT', 'TRN', 'STG', 'PRD'], Environment::codes());
    }

    public function test_labels_are_the_human_names(): void
    {
        self::assertSame('Test', Environment::Test->label());
        self::assertSame('Production', Environment::Production->label());
    }

    public function test_aliases_include_the_value_long_code_and_full_name(): void
    {
        self::assertSame(['TST', 'TEST', 'test'], Environment::Test->aliases());
        self::assertSame(['PRD', 'PROD', 'production'], Environment::Production->aliases());
    }

    public function test_try_from_alias_matches_values_and_aliases_case_insensitively(): void
    {
        self::assertSame(Environment::Test, Environment::tryFromAlias('TEST'));
        self::assertSame(Environment::Test, Environment::tryFromAlias('  test '));
        self::assertSame(Environment::Test, Environment::tryFromAlias('TST'));
        self::assertSame(Environment::Production, Environment::tryFromAlias('PROD'));
        self::assertSame(Environment::Development, Environment::tryFromAlias('development'));
    }

    public function test_try_from_alias_returns_null_for_an_unknown_spelling(): void
    {
        self::assertNull(Environment::tryFromAlias('nope'));
    }

    public function test_from_alias_resolves_or_throws(): void
    {
        self::assertSame(Environment::Production, Environment::fromAlias('production'));

        $this->expectException(ValueError::class);
        Environment::fromAlias('nope');
    }
}
