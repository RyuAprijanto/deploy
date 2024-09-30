<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;

    public function checkouts()
    {
        return $this->hasMany(Checkout::class, 'payment_type_id');
    }
}
