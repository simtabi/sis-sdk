<?php

declare(strict_types=1);

namespace Simtabi\SIS\Policy;

use Simtabi\SIS\Identifier\AliasCandidates;
use Simtabi\SIS\Profile\AliasDerivation;
use Simtabi\SIS\Profile\AliasGrammar;

/**
 * Derives human-memorable aliases from a legal entity name — SIM-STD-0001:2026 §5.2.
 *
 * Four letters give 456,976 combinations, so the space was never the constraint; the scarce resource is
 * DERIVABLE, MEMORABLE codes. The policy therefore WIDENS BEFORE IT MANGLES: it exhausts every 4-char
 * candidate, then 5, then 6, and only then falls back to a numeric discriminator. `ACMX` still reads like
 * Acme; `ACME2` reads like a database error.
 *
 * The vocabulary (legal suffixes, generic words, reserved aliases, length band) is profile data injected at
 * construction, so the ranking is identical for SIM and any custom profile. `candidates()` is pure and
 * deterministic — the ranking never leaves the core.
 */
final readonly class AliasPolicy
{
    /** @param list<string> $reserved */
    public function __construct(
        private AliasDerivation $derivation,
        private array $reserved,
        private AliasGrammar $grammar,
    ) {}

    /** The full ranked, de-duplicated, reserved-filtered candidate list, best first. */
    public function candidates(string $legalName): AliasCandidates
    {
        $ranked = [];
        $seen = [];

        $push = function (string $code) use (&$ranked, &$seen): void {
            $code = strtoupper($code);

            if ($code === '' || isset($seen[$code])) {
                return;
            }

            if (!$this->grammar->matches($code) || $this->isReserved($code)) {
                return;
            }

            $seen[$code] = true;
            $ranked[] = $code;
        };

        // Widen: every 4-char candidate, then 5, then 6.
        for ($length = $this->derivation->min; $length <= $this->derivation->max; $length++) {
            foreach ($this->deriveForLength($legalName, $length) as $candidate) {
                $push($candidate);
            }
        }

        // Only then a numeric discriminator (ACME2), the last resort.
        $base = $this->deriveForLength($legalName, $this->derivation->min)[0] ?? null;

        if ($base !== null) {
            for ($n = 2; $n <= 99; $n++) {
                $push(substr($base, 0, 3) . $n);
            }
        }

        return new AliasCandidates($legalName, $ranked);
    }

    public function isReserved(string $alias): bool
    {
        return in_array(strtoupper($alias), $this->reserved, true);
    }

    /** @return list<string> */
    public function reserved(): array
    {
        return $this->reserved;
    }

    /**
     * Ranked candidates of exactly one length: head + distinctive tail, truncation, initials, consonant
     * skeleton, a re-admitted generic word, then first-two + last-two.
     *
     * @return list<string>
     */
    private function deriveForLength(string $name, int $length): array
    {
        [$core, $all] = $this->normalise($name);

        if ($core === []) {
            return [];
        }

        $out = [];
        $push = function (string $code) use (&$out, $length): void {
            if ($code === '') {
                return;
            }

            $code = str_pad(substr($code, 0, $length), $length, $this->derivation->padding);

            if (ctype_alpha($code[0]) && !in_array($code, $out, true)) {
                $out[] = $code;
            }
        };

        $joined = implode('', $core);
        $joinedAll = implode('', $all);

        // Head + distinctive tail: turns AdelsaIQ into ADIQ rather than the flatter ADEL.
        if (strlen($joined) >= $length) {
            $push(substr($joined, 0, 2) . substr($joined, -($length - 2)));
        }

        $push($joined);

        if (count($core) > 1) {
            $push(implode('', array_map(static fn (string $w): string => $w[0], $core)) . substr($core[0], 1));
        }

        $push($joined[0] . str_replace($this->derivation->vowels, '', substr($joined, 1)));

        // Re-admit a generic word purely to break a tie (Northwind Traders vs Technologies).
        if (count($all) > count($core)) {
            $push(implode('', array_map(static fn (string $w): string => $w[0], $all)) . substr($all[0], 1));
            $push($joinedAll);
        }

        if (strlen($joined) >= 4) {
            $push(substr($joined, 0, 2) . substr($joined, -2));
        }

        return $out;
    }

    /** @return array{0: list<string>, 1: list<string>} core words (distinctive), all words */
    private function normalise(string $name): array
    {
        $value = strtoupper(self::transliterate($name));
        $value = str_replace('&', ' AND ', $value);
        $value = preg_replace('/[^A-Z0-9 ]+/', ' ', $value) ?? '';

        $words = array_values(array_filter(explode(' ', $value), static fn (string $w): bool => $w !== ''));

        while ($words !== [] && in_array(end($words), $this->derivation->legalSuffixes, true)) {
            array_pop($words);
        }

        $core = array_values(array_filter(
            $words,
            fn (string $w): bool => !in_array($w, $this->derivation->genericWords, true),
        ));

        return [$core === [] ? $words : $core, $words];
    }

    /** Fold accents, so "Café" and "Cafe" never yield two codes for one client. */
    private static function transliterate(string $value): string
    {
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);

            if (is_string($result)) {
                return $result;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }
}
