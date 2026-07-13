<!--
Thanks for contributing to the SIS SDK. Keep the change focused; unrelated changes belong in separate PRs.
By opening this PR you agree it is licensed under the repository's MIT license.
-->

## What and why

<!-- What does this change, and why is it needed? Link any issue with "Closes #123". -->

## Type of change

- [ ] Bug fix (non-breaking)
- [ ] New feature (non-breaking)
- [ ] Breaking change
- [ ] Documentation only
- [ ] Specification amendment (changes SIM-STD-0001:2026)

## Checklist

- [ ] `composer quality` passes locally (lint, Pint, deptrac, PHPStan level 10, PHPUnit).
- [ ] New behaviour is covered by tests; the SIM profile stays the byte-identical conformance oracle.
- [ ] The change respects the zero-dependency boundary (no `Illuminate\`/`Laranail\`/framework imports).
- [ ] The fixed guarantees (grammar shape, ISO 7064 check, lifecycle) are unchanged, or this is a spec amendment.
- [ ] No AI/assistant attribution in commits or this PR.
