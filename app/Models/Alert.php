<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

enum AlertLevel
{
    case Info;
    case Warning;
    case Error;
    case Catastrophy;
}

class Alert extends Model
{
    use HasFactory;
}
