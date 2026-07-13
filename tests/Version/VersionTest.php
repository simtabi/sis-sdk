<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Version;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Exception\InvalidVersionException;
use Simtabi\SIS\Sis;

final class VersionTest extends TestCase
{
    private Sis $sis;

    protected function setUp(): void
    {
        $this->sis = new Sis;
    }

    public function test_parses_product_and_semver(): void
    {
        $v = $this->sis->version('MALISA-1.4.3+20260712.a91f2c');

        self::assertSame('MALISA', $v->product);
        self::assertSame(1, $v->major);
        self::assertSame('20260712.a91f2c', $v->build);
        self::assertFalse($v->isPreRelease());
    }

    public function test_pre_release_precedence_is_numeric_not_lexical(): void
    {
        // Audit bug 1: strcmp put rc.10 below rc.2. Semver §11 compares numeric identifiers numerically.
        self::assertLessThan(0, $this->sis->version('MALISA-1.0.0-rc.2')->compare($this->sis->version('MALISA-1.0.0-rc.10')));
    }

    public function test_pre_release_sorts_below_release(): void
    {
        self::assertLessThan(0, $this->sis->version('MALISA-1.0.0-rc.1')->compare($this->sis->version('MALISA-1.0.0')));
        self::assertGreaterThan(0, $this->sis->version('MALISA-2.0.0-rc.1')->compare($this->sis->version('MALISA-1.4.2')));
    }

    public function test_more_fields_wins_when_prefixes_are_equal(): void
    {
        self::assertLessThan(0, $this->sis->version('MALISA-1.0.0-alpha')->compare($this->sis->version('MALISA-1.0.0-alpha.1')));
    }

    public function test_build_metadata_is_ignored_for_ordering(): void
    {
        self::assertSame(0, $this->sis->version('MALISA-1.0.0+a')->compare($this->sis->version('MALISA-1.0.0+b')));
    }

    public function test_comparing_different_products_throws(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->sis->version('MALISA-1.0.0')->compare($this->sis->version('OTHER-1.0.0'));
    }
}
