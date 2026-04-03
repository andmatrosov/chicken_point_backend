<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case ACTIVE = 'active';
    case SUBMITTED = 'submitted';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
}
