<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Profile;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Profile\SisProfile;
use Simtabi\SIS\Sis;

/**
 * A profile is data, so it round-trips through a plain array (`fromArray`) and a PHP file that returns one
 * (`fromFile`) — no framework, no config loader, just `require`.
 */
final class ProfileArrayTest extends TestCase
{
    /** @return array<string, mixed> */
    private function acmeArray(): array
    {
        return [
            'issuer' => 'ACME',
            'serials' => ['global_start' => 500000, 'scoped_start' => 1],
            'aliases' => ['reserved' => ['ACME', 'ROOT']],
            'capacity_threshold' => 0.75,
            'classes' => [
                ['code' => 'CST', 'label' => 'Customer', 'uses_alias' => true],
                ['code' => 'ORD', 'label' => 'Order', 'scoped' => true],
                ['code' => 'STD', 'label' => 'Standard', 'serial_start' => 1],
            ],
        ];
    }

    public function test_from_array_builds_a_working_profile(): void
    {
        $profile = SisProfile::fromArray($this->acmeArray());

        self::assertSame('ACME', $profile->issuer());
        self::assertSame(500000, $profile->serials()->globalStart);
        self::assertEqualsWithDelta(0.75, $profile->capacityThreshold(), 1e-9);
        self::assertSame(['CST', 'ORD', 'STD'], $profile->classes()->codes());

        // Serial starts resolve from the class form, with STD's explicit override honoured.
        self::assertSame(500000, $profile->classes()->class('CST')->serialStart());
        self::assertSame(1, $profile->classes()->class('ORD')->serialStart());
        self::assertSame(1, $profile->classes()->class('STD')->serialStart());

        $sis = new Sis($profile);
        $id = $sis->codec()->mint($sis->class('CST'), 500000);

        self::assertStringStartsWith('ACME-CST-500000-', (string) $id);
        self::assertTrue($sis->validate((string) $id));
        self::assertTrue($sis->isReservedAlias('ACME'));
    }

    public function test_from_file_requires_a_php_array(): void
    {
        $profile = SisProfile::fromFile(__DIR__ . '/Fixtures/acme_profile.php');

        self::assertSame('ACME', $profile->issuer());
        self::assertSame(['CST', 'ORD', 'STD'], $profile->classes()->codes());

        $sis = new Sis($profile);
        $id = $sis->codec()->mint($sis->class('ORD'), 1, 'BETA');

        self::assertStringStartsWith('ACME-ORD-BETA-000001-', (string) $id);
        self::assertTrue($sis->validate((string) $id));
    }
}
