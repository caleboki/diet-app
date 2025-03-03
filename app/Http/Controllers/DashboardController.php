<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\UserDietaryProfile;
use App\Models\DietaryRestriction;
use App\Models\MedicalCondition;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the personalized dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get the active dietary profile with related data
        $activeProfile = $user->activeDietaryProfile()
            ->with([
                'dietaryRestrictions',
                'medicalConditions' => function($query) {
                    $query->orderBy('severity', 'desc');
                }
            ])
            ->first();
            
        // Format the active profile data for the frontend
        $formattedActiveProfile = null;
        if ($activeProfile) {
            $formattedActiveProfile = [
                'id' => $activeProfile->id,
                'profile_name' => $activeProfile->name,
                'description' => $activeProfile->description,
                'is_active' => $activeProfile->is_active,
                'created_at' => $activeProfile->created_at,
                'updated_at' => $activeProfile->updated_at,
                'medical_conditions' => $activeProfile->medicalConditions,
                'dietary_restrictions' => $activeProfile->dietaryRestrictions,
            ];
        }
            
        // Get restriction and condition statistics
        $stats = [];
        if ($activeProfile) {
            // Group medical conditions by severity
            $conditionsBySeverity = [
                'mild' => 0,
                'moderate' => 0,
                'severe' => 0
            ];
            
            foreach ($activeProfile->medicalConditions as $condition) {
                $conditionsBySeverity[$condition->pivot->severity]++;
            }
            
            $stats = [
                'totalRestrictions' => $activeProfile->dietaryRestrictions->count(),
                'totalMedicalConditions' => $activeProfile->medicalConditions->count(),
                'conditionsBySeverity' => $conditionsBySeverity,
            ];
            
            // Add debugging
            Log::info('Dashboard stats data:', ['stats' => $stats]);
        }
        
        // Get latest dietary profiles
        $recentProfiles = $user->dietaryProfiles()
            ->select('id', 'name as profile_name', 'description', 'is_active', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();
        
        // Get total count of dietary profiles
        $totalProfilesCount = $user->dietaryProfiles()->count();
        
        // Get common dietary restrictions (for recommendations)
        $commonRestrictions = DietaryRestriction::withCount('userProfiles')
            ->orderBy('user_profiles_count', 'desc')
            ->take(5)
            ->get();
            
        return Inertia::render('Dashboard', [
            'activeProfile' => $formattedActiveProfile,
            'stats' => $stats,
            'recentProfiles' => $recentProfiles,
            'totalProfilesCount' => $totalProfilesCount,
            'commonRestrictions' => $commonRestrictions,
            'recommendedRecipes' => [] // Placeholder for future recipe recommendation
        ]);
    }
}
