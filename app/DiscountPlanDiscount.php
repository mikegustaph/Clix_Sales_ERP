<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountPlanDiscount extends Model
{
    use HasFactory;

    protected $fillable =['discount_plan_id', 'discount_id'];
}
