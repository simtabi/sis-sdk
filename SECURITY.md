# Security policy

## Reporting a vulnerability

Please report security vulnerabilities privately. **Do not open a public issue** for a suspected
vulnerability.

Email **opensource@simtabi.com** with the affected version, a description and impact, and steps to
reproduce. You can also use GitHub's private
[security advisory](https://github.com/simtabi/sis-sdk/security/advisories/new) flow.

We aim to acknowledge within three business days and to provide a remediation timeline after triage.
Please allow a reasonable window to release a fix before public disclosure.

## Scope

`simtabi/sis-sdk` is a security-first identifier engine. Taken seriously:

- Forging a valid check character, or a defect that lets a malformed identifier verify.
- A grammar/parse defect that accepts an out-of-spec identifier or rejects a conformant one.
- A profile-configuration path that silently weakens the ISO 7064 check or the lifecycle guarantees.

## Supported versions

While pre-1.0, only the latest `0.1.x` tag is supported. Fixes land on the current tag.
