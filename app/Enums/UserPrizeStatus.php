<?php

namespace App\Enums;

enum UserPrizeStatus: string
{
    case PENDING = 'pending';
    case ISSUED = 'issued';
    case CANCELED = 'canceled';
}
