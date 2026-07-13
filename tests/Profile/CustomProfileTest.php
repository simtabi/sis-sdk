<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Profile;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Exception\ScopeMismatchException;
use Simtabi\SIS\Profile\SerialRules;
use Simtabi\SIS\Profile\SisProfile;
use Simtabi\SIS\Sis;
use Simtabi\SIS\Support\CheckCharacters;

/**
 * The whole point of the refactor: a custom profile drives the same total functions. A profile with the
 * `ACME` issuer and its own classes mints valid `ACME-…` identifiers, with the same ISO 7064 check
 * characters, and rejects anything malformed — including a perfectly valid SIM identifier, which belongs to
 * a different issuer.
 */
final class CustomProfileTest extends TestCase
{
    private function acme(): Sis
    {
        // The class token is [A-Z]{3,4}, so a custom profile may use human-readable four-letter codes
        // (CUST) alongside three-letter ones (ORD).
        return new Sis(
            SisProfile::builder()
                ->issuer('ACME')
                ->class('CUST', label: 'Customer')
                ->class('ORD', label: 'Order', scoped: true)
                ->build(),
        );
    }

    public function test_mints_a_valid_global_identifier_with_correct_check(): void
    {
        $acme = $this->acme();
        $id = $acme->codec()->mint($acme->class('CUST'), 100001);

        self::assertStringStartsWith('ACME-CUST-100001-', (string) $id);
        self::assertSame(CheckCharacters::for($id->core()), $id->check);
        self::assertTrue($acme->validate((string) $id));
        self::assertSame('CUST', $acme->identify((string) $id)?->code);
    }

    public function test_mints_a_valid_scoped_identifier(): void
    {
        $acme = $this->acme();
        $id = $acme->codec()->mint($acme->class('ORD'), 1, 'ACME');

        self::assertStringStartsWith('ACME-ORD-ACME-000001-', (string) $id);
        self::assertSame(CheckCharacters::for($id->core()), $id->check);
        self::assertTrue($acme->validate((string) $id));
        self::assertTrue($id->is('ORD'));
    }

    public function test_scoped_and_global_forms_are_enforced(): void
    {
        $acme = $this->acme();

        $this->expectException(ScopeMismatchException::class);
        (void) $acme->codec()->mint($acme->class('ORD'), 1);
    }

    public function test_rejects_malformed_and_foreign_identifiers(): void
    {
        $acme = $this->acme();

        self::assertFalse($acme->validate('ACME-CUST-100001-ZZ'), 'wrong check characters');
        self::assertFalse($acme->validate('ACME-CUST-1-AB'), 'serial too short');
        self::assertFalse($acme->validate('SIM-CLT-100001-9O'), 'a valid SIM identifier is foreign to ACME');
        self::assertFalse($acme->validate('ACME-XXXX-100001-AB'), 'unknown class');
    }

    public function test_builder_validates_its_invariants(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SisProfile::builder()->build();   // no issuer
    }

    public function test_builder_accepts_a_four_letter_code(): void
    {
        $profile = SisProfile::builder()->issuer('ACME')->class('CUST', label: 'Customer')->build();

        self::assertTrue($profile->classes()->has('CUST'));
    }

    public function test_builder_rejects_a_code_of_the_wrong_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SisProfile::builder()->issuer('ACME')->class('CUSTO')->build();   // five letters is too long
    }

    public function test_builder_rejects_a_duplicate_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SisProfile::builder()->issuer('ACME')->class('CUST')->class('CUST')->build();
    }

    public function test_builder_rejects_a_default_width_outside_the_band(): void
    {
        // A self-inconsistent band (default below the minimum) must fail fast at BUILD time with the real
        // cause — not silently build and then throw a misleading MalformedIdentifierException on every mint,
        // when the codec re-parses a serial padded to a width the grammar band rejects.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Serial default width 6 is outside the profile band [7, 9]');

        SisProfile::builder()
            ->issuer('ACME')
            ->class('CUST')
            ->serials(new SerialRules(minWidth: 7, maxWidth: 9, defaultWidth: 6))
            ->build();
    }
}
