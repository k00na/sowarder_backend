<?php

namespace App\Enums;

enum Currency: string
{
    case Usd = 'USD';
    case Mvr = 'MVR';

    /**
     * Number of decimal subunits (minor units per major unit).
     * USD, MVR, and future EUR all use 2 in MVP.
     */
    public function minorUnitsPerMajor(): int
    {
        return 100;
    }
}
