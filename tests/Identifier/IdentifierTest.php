<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Identifier;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Exception\CheckCharacterMismatchException;
use Simtabi\SIS\Exception\ScopeMismatchException;
use Simtabi\SIS\Sis;

/**
 * Zero-config parity: `new Sis()` mints byte-for-byte the specimens the hardcoded core produced. If any of
 * these expected strings change, the SIM profile has drifted from the frozen specification.
 */
final class IdentifierTest extends TestCase
{
    /** @return array<string, array{SimClass, int, ?string, string}> */
    public static function specimens(): array
    {
        return [
            'client' => [SimClass::CLIENT, 100001, null, 'SIM-CLT-100001-9O'],
            'person' => [SimClass::PERSON, 100001, null, 'SIM-PRS-100001-FA'],
            'invoice scoped' => [SimClass::INVOICE, 1, 'ADIQ', 'SIM-INV-ADIQ-000001-VY'],
            'sow scoped' => [SimClass::SOW, 1, 'ADIQ', 'SIM-SOW-ADIQ-000001-NZ'],
        ];
    }

    #[DataProvider('specimens')]
    public function test_mint_reproduces_the_specimen(SimClass $class, int $serial, ?string $scope, string $expected): void
    {
        $sis = new Sis;

        self::assertSame($expected, (string) $sis->codec()->mint($sis->class($class), $serial, $scope));
        self::assertTrue($sis->validate($expected));
        self::assertSame($class->value, $sis->identify($expected)?->code);
    }

    public function test_mint_and_parse_round_trip(): void
    {
        $sis = new Sis;
        $id = $sis->codec()->mint($sis->class(SimClass::INVOICE), 42, 'ADIQ');
        $parsed = $sis->parse((string) $id);

        self::assertTrue($id->equals($parsed));
        self::assertSame(42, $parsed->serial);
        self::assertSame('ADIQ', $parsed->scope);
        self::assertTrue($parsed->is(SimClass::INVOICE->value));
    }

    public function test_rejects_transposed_alias_and_serial(): void
    {
        $sis = new Sis;

        self::assertFalse($sis->validate('SIM-INV-ADQI-000001-VY'));
        self::assertFalse($sis->validate('SIM-PRS-100010-FA'));
    }

    public function test_rejects_bad_check_with_a_mismatch_exception(): void
    {
        $this->expectException(CheckCharacterMismatchException::class);
        (new Sis)->parse('SIM-PRS-100001-ZZ');
    }

    public function test_scoped_class_requires_a_scope(): void
    {
        $sis = new Sis;
        $this->expectException(ScopeMismatchException::class);
        (void) $sis->codec()->mint($sis->class(SimClass::INVOICE), 1);
    }

    public function test_global_class_rejects_a_scope(): void
    {
        $sis = new Sis;
        $this->expectException(ScopeMismatchException::class);
        (void) $sis->codec()->mint($sis->class(SimClass::PERSON), 100001, 'ADIQ');
    }

    public function test_comparison_ignores_case(): void
    {
        $sis = new Sis;

        // Canonical form uses hyphens; §2.4 makes comparison case-insensitive.
        $upper = $sis->parse('SIM-PRS-100001-FA');
        $lower = $sis->parse('sim-prs-100001-fa');

        self::assertTrue($upper->equals($lower));
        self::assertSame('SIMPRS100001FA', $upper->comparable());
    }
}
