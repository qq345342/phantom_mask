<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyMasks extends Model
{
    protected $fillable = ['pharmacy_id', 'mask_name', 'mask_price'];
}
