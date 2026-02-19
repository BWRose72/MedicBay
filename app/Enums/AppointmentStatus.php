<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case NoShow = 'Didn\'t show up';
}
