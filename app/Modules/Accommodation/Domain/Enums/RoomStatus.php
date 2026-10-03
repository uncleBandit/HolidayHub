<?php

namespace App\Modules\Accommodation\Domain\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';
}
