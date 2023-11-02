<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountPlanCustomer extends Model
{
    use HasFactory;

    protected $fillable = ['discount_plan_id', 'customer_id'];
}
