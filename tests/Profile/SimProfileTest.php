<?php

declare(strict_types=1);

namespace Simtabi\SIS\Tests\Profile;

use PHPUnit\Framework\TestCase;
use Simtabi\SIS\Enums\Environment;
use Simtabi\SIS\Enums\SimClass;
use Simtabi\SIS\Profile\ClassRegister;
use Simtabi\SIS\Profile\Sim\SimProfile;

/**
 * The built-in SIM profile is now data — `SimProfile::create()` loads `config/sim.php` through
 * `SisProfile::fromFile()`. These are the same register invariants the old native class enum guaranteed,
 * asserted against `SimProfile::create()` / `ClassRegister`, so the config file can never drift from the
 * frozen specification.
 */
final class SimProfileTest extends TestCase
{
    private function register(): ClassRegister
    {
        return SimProfile::create()->classes();
    }

    public function test_the_config_file_drives_a_complete_profile(): void
    {
        $profile = SimProfile::create();

        // The config file supplies every profile-level value the core once hardcoded.
        self::assertSame('SIM', $profile->issuer());
        self::assertSame('-', $profile->separator());
        self::assertCount(22, $profile->classes()->all());
    }

    public function test_serial_starts_resolve_the_std_exception(): void
    {
        $register = $this->register();

        // Audit bug 6: STD is a global class that starts at 000001, not 100001 (§3.4).
        self::assertSame(1, $register->class(SimClass::STANDARD->value)->serialStart());
        self::assertSame(100001, $register->class(SimClass::CLIENT->value)->serialStart());
        self::assertSame(100001, $register->class(SimClass::PERSON->value)->serialStart());
        self::assertSame(1, $register->class(SimClass::INVOICE->value)->serialStart());
    }

    public function test_permits_subtype_matches_the_storage_layer(): void
    {
        $register = $this->register();

        // Audit bug 5: a class with no vocabulary permits NO subtype, matching the SQL CHECK.
        self::assertFalse($register->class(SimClass::CLIENT->value)->permitsSubtype('LAP'));
        self::assertFalse($register->class(SimClass::INVOICE->value)->permitsSubtype('ANY'));
        self::assertTrue($register->class(SimClass::ASSET->value)->permitsSubtype('LAP'));
        self::assertTrue($register->class(SimClass::PERSON->value)->permitsSubtype('ENG'));
        self::assertFalse($register->class(SimClass::ASSET->value)->permitsSubtype('FOO'));
    }

    public function test_scoped_and_aliased_classes_match_the_spec(): void
    {
        $register = $this->register();

        self::assertTrue($register->class(SimClass::INVOICE->value)->isScoped());
        self::assertFalse($register->class(SimClass::CLIENT->value)->isScoped());
        self::assertTrue($register->class(SimClass::CLIENT->value)->usesAlias());
        self::assertFalse($register->class(SimClass::INVOICE->value)->usesAlias());
    }

    public function test_the_environment_class_carries_the_environment_codes_as_subtypes(): void
    {
        $env = $this->register()->class(SimClass::ENVIRONMENT->value);

        self::assertSame(Environment::codes(), $env->subtypes());
        self::assertTrue($env->permitsSubtype('PRD'));
        self::assertTrue($env->permitsSubtype('DEV'));
    }
}
