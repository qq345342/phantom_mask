<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPurchaseHistories extends Model
{
    protected $fillable = ['user_id', 'pharmacy_name', 'mask_name', 'transaction_amount', 'transaction_date'];
}
