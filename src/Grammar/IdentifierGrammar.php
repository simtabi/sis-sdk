<?php

declare(strict_types=1);

namespace Simtabi\SIS\Grammar;

use Simtabi\SIS\Profile\SisProfile;

/**
 * The two identifier forms, compiled from a profile — SIM-STD-0001:2026 §2.
 *
 *   Form G (global):  {ISSUER}-{CLASS}-{SERIAL}-{CHECK}
 *   Form S (scoped):  {ISSUER}-{CLASS}-{SCOPE}-{SERIAL}-{CHECK}
 *
 * Only the issuer (quoted), the scope band (from the alias grammar), and the serial width band vary per
 * profile. The grammar SHAPE is fixed by the specification and NOT configurable: the class token is always
 * `[A-Z]{3,4}` (three or four letters — three-letter codes such as the SIM register stay valid; four-letter
 * codes like `CUST` are permitted for human-readable custom vocabularies) and the check is always
 * `[0-9A-Z]{2}`. For the SIM profile (all three-letter codes) this compiles byte-for-byte to the original
 * frozen literals.
 */
final readonly class IdentifierGrammar
{
    private const string CLASS_TOKEN = '[A-Z]{3,4}';

    private const string CHECK_TOKEN = '[0-9A-Z]{2}';

    private string $formG;

    private string $formS;

    public function __construct(SisProfile $profile)
    {
        $issuer = preg_quote($profile->issuer(), '/');
        $separator = $profile->separator();
        $serial = '\d{' . $profile->serials()->minWidth . ',' . $profile->serials()->maxWidth . '}';
        $scope = $profile->aliasGrammar()->fragment();

        $this->formG = '/^' . $issuer . $separator . '(' . self::CLASS_TOKEN . ')'
            . $separator . '(' . $serial . ')'
            . $separator . '(' . self::CHECK_TOKEN . ')$/';

        $this->formS = '/^' . $issuer . $separator . '(' . self::CLASS_TOKEN . ')'
            . $separator . '(' . $scope . ')'
            . $separator . '(' . $serial . ')'
            . $separator . '(' . self::CHECK_TOKEN . ')$/';
    }

    public function formG(): string
    {
        return $this->formG;
    }

    public function formS(): string
    {
        return $this->formS;
    }
}
