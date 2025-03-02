<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserDietaryProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
    ];
    
    /**
     * Get the user that owns the dietary profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the medical conditions for the dietary profile.
     */
    public function medicalConditions(): BelongsToMany
    {
        return $this->belongsToMany(MedicalCondition::class, 'user_medical_conditions')
            ->withPivot('severity')
            ->withTimestamps();
    }

    /**
     * Get the dietary restrictions for the dietary profile.
     */
    public function dietaryRestrictions(): BelongsToMany
    {
        return $this->belongsToMany(DietaryRestriction::class, 'user_dietary_restrictions')
            ->withPivot('severity', 'notes')
            ->withTimestamps();
    }
}
