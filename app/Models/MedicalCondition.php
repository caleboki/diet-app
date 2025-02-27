<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalCondition extends Model
{
    protected $fillable = ['name', 'description'];

    public function dietaryRestrictions()
    {
        return $this->hasMany(DietaryRestriction::class);
    }
}
