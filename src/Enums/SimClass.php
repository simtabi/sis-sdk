<?php

declare(strict_types=1);

namespace Simtabi\SIS\Enums;

/**
 * The 22 SIM class codes as a backed enum — SIM-STD-0001:2026 §3.
 *
 * The class register itself is data-driven (see `SimProfile`), but consumers still want a
 * compile-time-checked, greppable handle on each code instead of a bare `'CLT'` literal. Each case is
 * backed by exactly its three-letter class token, so `SimClass::CLIENT->value === 'CLT'`. Resolve a
 * definition with `(new Sis())->class(SimClass::CLIENT)` or `SimProfile::create()->classes()`.
 */
enum SimClass: string
{
    // Party and organisation (§3.1)
    case CLIENT = 'CLT';
    case PERSON = 'PRS';
    case VENDOR = 'VND';
    case DEPARTMENT = 'DPT';

    // Commercial (§3.2)
    case PROJECT = 'PRJ';
    case SOW = 'SOW';
    case CHANGE_ORDER = 'CHG';
    case MILESTONE = 'MIL';
    case QUOTE = 'QUO';
    case INVOICE = 'INV';
    case CREDIT_NOTE = 'CRN';

    // Product (§3.3)
    case PRODUCT = 'PRD';
    case SERVICE = 'SVC';
    case COMPONENT = 'CMP';
    case RELEASE = 'REL';

    // Asset and governance (§3.4)
    case ASSET = 'AST';
    case DOCUMENT = 'DOC';
    case STANDARD = 'STD';
    case DECISION = 'ADR';

    // Operations (§3.5)
    case TICKET = 'TKT';
    case INCIDENT = 'INC';
    case ENVIRONMENT = 'ENV';
}
