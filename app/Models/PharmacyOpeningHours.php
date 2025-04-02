<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyOpeningHours extends Model
{
    protected $fillable = ['pharmacy_id', 'week', 'start_time', 'end_time'];
}
