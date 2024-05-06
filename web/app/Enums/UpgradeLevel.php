<?php

namespace App\Enums;

enum UpgradeLevel: string
{
    case ALPHA = '50';
    case BETA = '100';
    case RC = '150';
    case STABLE = '200';
    case ANY = 'any';
}
