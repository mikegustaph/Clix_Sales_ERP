<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RewardPointSetting extends Model
{
    protected $fillable = ["per_point_amount", "minimum_amount", "duration", "type", "is_active"];
}
