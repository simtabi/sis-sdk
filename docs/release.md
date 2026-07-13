# Release

How the SDK is versioned and tagged, and how the packages that consume it resolve it.

## A standalone, zero-dependency package

The SDK is a pure PHP package with **no Composer runtime dependencies** — it requires only `php ^8.5`. It is developed and tagged on its own, independent of any consumer:

| Package | Repository | Role |
|---------|------------|------|
| `simtabi/sis-sdk` | `github.com/simtabi/sis-sdk` | This package — the framework-free SDK engine. Owned by the `simtabi` org. |
| `laranail/sis-wrapper` | `github.com/laranail/sis-wrapper` | The Laravel binding that consumes this SDK. Owned by the `laranail` org. |

There is no monorepo and no `git subtree` split — the SDK was extracted from the former `laranail/sis` monorepo into this repo and is now tagged on its own.

## Consumed via VCS, not Packagist

The SIS family resolves inter-package dependencies through **git VCS repositories**, not Packagist (Packagist is treated as unreliable for this family — force-pushed history leaves stale cached clones). So the SDK is **not** published or updated on Packagist as part of routine work.

A consumer adds the SDK as a `vcs` repository in its own `composer.json` and requires it on `^0.1`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/simtabi/sis-sdk" }
],
"require": {
    "simtabi/sis-sdk": "^0.1"
}
```

## Single moving `v0.1.0` tag, pre-1.0

While pre-stable, the repo keeps **one `v0.1.0` tag and *moves* it on each change** — the `Initial release` commit is amended (or a follow-up committed) and `v0.1.0` re-pointed. No new SemVer versions are cut yet. A `^0.1` constraint resolves the latest tag from the VCS repo, so a consumer picks up the moved tag on the next `composer update`.

The repo carries a `branch-alias` of `dev-main → 0.1.x-dev`, so a `path` or `dev-main` checkout still satisfies `^0.1`. It sets `minimum-stability: stable` with `prefer-stable: true`. Every tagged release carries a description sourced from the matching `CHANGELOG.md` section.

## The specification ships with the SDK

This package is the **reference implementation of `SIM-STD-0001:2026`**, and the normative document ([`SIM-STD-0001-2026.md`](../SIM-STD-0001-2026.md)) lives in this repo at the root. Downstream docs (including the wrapper's) link to it here rather than duplicating it.

## Release versions (the domain, not the package)

Note the distinction: this page is about *package* releases. The system itself also models **product release versions** (§7.2) as a first-class value — `MALISA-1.4.2`, `MALISA-2.0.0-rc.1` — ordered by Semantic Versioning 2.0.0. That is domain data the engine parses and compares (`Sis::version()`), unrelated to how this Composer package is tagged.

---

[← Docs index](../README.md#documentation)
