<?php

namespace App\Http\Controllers;

use App\Models\UserDietaryProfile;
use App\Models\MedicalCondition;
use App\Models\DietaryRestriction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserDietaryProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $profiles = auth()->user()->dietaryProfiles()
                ->with(['dietaryRestrictions', 'medicalConditions'])
                ->get();

            Log::info('Raw profiles data', ['count' => count($profiles)]);

            if (count($profiles) > 0) {
                Log::info('First raw profile', ['id' => $profiles->first()->id]);
            }

            $transformed = $profiles->map(function ($profile) {
                try {
                    $medical_conditions = [];
                    $dietary_restrictions = [];

                    if ($profile->medicalConditions) {
                        $medical_conditions = $profile->medicalConditions->map(function ($condition) {
                            return [
                                'id' => $condition->id,
                                'name' => $condition->name,
                                'pivot' => $condition->pivot ? [
                                    'severity' => $condition->pivot->severity,
                                ] : null,
                            ];
                        })->toArray();
                    }

                    if ($profile->dietaryRestrictions) {
                        $dietary_restrictions = $profile->dietaryRestrictions->map(function ($restriction) {
                            return [
                                'id' => $restriction->id,
                                'name' => $restriction->name,
                                'pivot' => $restriction->pivot ? [
                                    'severity' => $restriction->pivot->severity,
                                ] : null,
                            ];
                        })->toArray();
                    }

                    // Transform to consistent format with dashboard
                    return [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'profile_name' => $profile->name, // For consistency
                        'description' => $profile->description,
                        'is_active' => $profile->is_active,
                        'created_at' => $profile->created_at,
                        'updated_at' => $profile->updated_at,
                        'medical_conditions' => $medical_conditions,
                        'dietary_restrictions' => $dietary_restrictions,
                    ];
                } catch (\Exception $e) {
                    Log::error('Error transforming profile', [
                        'profile_id' => $profile->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Return a minimal profile when error occurs
                    return [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'profile_name' => $profile->name,
                        'description' => $profile->description,
                        'is_active' => $profile->is_active,
                        'created_at' => $profile->created_at,
                        'updated_at' => $profile->updated_at,
                        'medical_conditions' => [],
                        'dietary_restrictions' => [],
                        'error' => 'Error processing profile data'
                    ];
                }
            });
            
            // Add debugging log
            Log::info('Profiles data for index page', [
                'count' => count($transformed),
                'sample' => $transformed->first()
            ]);
            
            return Inertia::render('DietaryProfile/Index', [
                'profiles' => $transformed,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in index method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('DietaryProfile/Index', [
                'profiles' => [],
                'error' => 'Error retrieving profiles. Please try again later.'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $medicalConditions = MedicalCondition::orderBy('name')
            ->get()
            ->map(function ($condition) {
                // Add a flag to indicate if this is a custom condition
                return array_merge($condition->toArray(), [
                    'is_custom' => !is_null($condition->user_id),
                ]);
            });
        $commonDietaryRestrictions = DietaryRestriction::orderBy('name')->get();
        
        // Define the steps for the multi-step form
        $steps = [
            'medical-conditions' => 'Medical Conditions',
            'dietary-restrictions' => 'Dietary Restrictions',
            'profile-details' => 'Profile Details',
            'review' => 'Review',
        ];
        
        return Inertia::render('DietaryProfile/Create', [
            'medicalConditions' => $medicalConditions,
            'commonDietaryRestrictions' => $commonDietaryRestrictions,
            'steps' => $steps,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'medical_conditions' => 'array',
            'medical_conditions.*.id' => 'required|exists:medical_conditions,id',
            'medical_conditions.*.severity' => 'required|in:mild,moderate,severe',
            'dietary_restrictions' => 'array',
            'dietary_restrictions.*.id' => 'required|exists:dietary_restrictions,id',
            'dietary_restrictions.*.notes' => 'nullable|string|max:1000',
        ]);

        // If user has an active profile, set it to inactive
        $user = Auth::user();
        $user->dietaryProfiles()->update(['is_active' => false]);

        // Create new profile
        $profile = new UserDietaryProfile();
        $profile->user_id = $user->id;
        $profile->name = $validated['name'];
        $profile->description = $validated['description'] ?? null;
        $profile->is_active = true;
        $profile->save();
        
        // Sync medical conditions with severity
        $medicalConditionData = [];
        foreach ($validated['medical_conditions'] ?? [] as $condition) {
            $medicalConditionData[$condition['id']] = ['severity' => $condition['severity']];
        }
        $profile->medicalConditions()->sync($medicalConditionData);

        // Determine and sync dietary restrictions with calculated severity
        $this->syncDietaryRestrictionsWithCalculatedSeverity($profile, $validated['dietary_restrictions'] ?? []);

        // Return to the dashboard instead of the dietary-profile index
        return redirect()->route('dashboard')
            ->with('success', 'Dietary profile created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDietaryProfile $userDietaryProfile)
    {
        $this->authorize('view', $userDietaryProfile);
        
        $userDietaryProfile->load(['dietaryRestrictions', 'medicalConditions', 'user']);
        
        return Inertia::render('DietaryProfile/Show', [
            'profile' => $userDietaryProfile,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserDietaryProfile $userDietaryProfile)
    {
        $this->authorize('update', $userDietaryProfile);
        
        $userDietaryProfile->load(['dietaryRestrictions', 'medicalConditions']);
        $medicalConditions = MedicalCondition::all();
        $commonDietaryRestrictions = DietaryRestriction::where('is_common_allergen', true)->get();
        
        return Inertia::render('DietaryProfile/Edit', [
            'profile' => $userDietaryProfile,
            'medicalConditions' => $medicalConditions,
            'commonDietaryRestrictions' => $commonDietaryRestrictions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserDietaryProfile  $userDietaryProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserDietaryProfile $userDietaryProfile)
    {
        // Authorize the request
        $this->authorize('update', $userDietaryProfile);
        
        // Debug the incoming request
        Log::info('Updating dietary profile', [
            'profile_id' => $userDietaryProfile->id,
            'request_data' => $request->all()
        ]);
        
        // Check if this is just setting the profile as active
        if ($request->has('is_active') && count($request->all()) <= 2) {
            return $this->setActive($userDietaryProfile);
        }
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'medical_conditions' => 'array',
                'medical_conditions.*.id' => 'required|exists:medical_conditions,id',
                'medical_conditions.*.severity' => 'required|in:mild,moderate,severe',
                'dietary_restrictions' => 'array',
                'dietary_restrictions.*.id' => 'required|exists:dietary_restrictions,id',
                'dietary_restrictions.*.notes' => 'nullable|string|max:1000',
            ]);
            
            Log::info('Validated data for profile update', [
                'profile_id' => $userDietaryProfile->id,
                'name' => $validated['name'],
                'medical_conditions' => isset($validated['medical_conditions']) ? $validated['medical_conditions'] : [],
                'medical_conditions_count' => isset($validated['medical_conditions']) ? count($validated['medical_conditions']) : 0,
                'dietary_restrictions_count' => isset($validated['dietary_restrictions']) ? count($validated['dietary_restrictions']) : 0
            ]);

            // Update profile
            $userDietaryProfile->name = $validated['name'];
            $userDietaryProfile->description = $validated['description'] ?? null;
            $userDietaryProfile->save();

            // Enable query logging
            \DB::enableQueryLog();
            
            // Sync medical conditions with severity
            $medicalConditionData = [];
            if (isset($validated['medical_conditions']) && is_array($validated['medical_conditions'])) {
                foreach ($validated['medical_conditions'] as $condition) {
                    if (isset($condition['id']) && isset($condition['severity'])) {
                        $medicalConditionData[$condition['id']] = ['severity' => $condition['severity']];
                    } else {
                        Log::warning('Malformed medical condition data', [
                            'condition' => $condition
                        ]);
                    }
                }
                
                Log::info('Syncing medical conditions', [
                    'profile_id' => $userDietaryProfile->id,
                    'medical_condition_data' => $medicalConditionData,
                    'condition_count' => count($medicalConditionData)
                ]);
                
                try {
                    $syncResult = $userDietaryProfile->medicalConditions()->sync($medicalConditionData);
                    
                    // Log the queries
                    Log::info('SQL Queries for medical conditions sync', [
                        'queries' => \DB::getQueryLog()
                    ]);
                    
                    Log::info('Medical conditions sync result', [
                        'attached' => $syncResult['attached'],
                        'detached' => $syncResult['detached'],
                        'updated' => $syncResult['updated']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error syncing medical conditions', [
                        'error' => $e->getMessage(),
                        'medical_condition_data' => $medicalConditionData,
                        'queries' => \DB::getQueryLog()
                    ]);
                    throw $e;
                }
            } else {
                Log::warning('No medical conditions provided or invalid format', [
                    'medical_conditions' => $validated['medical_conditions'] ?? null
                ]);
                // Clear all existing medical conditions if none provided
                $userDietaryProfile->medicalConditions()->sync([]);
                
                // Log the queries
                Log::info('SQL Queries for clearing medical conditions', [
                    'queries' => \DB::getQueryLog()
                ]);
            }
            
            // Disable query logging
            \DB::disableQueryLog();

            // Determine and sync dietary restrictions with calculated severity
            $this->syncDietaryRestrictionsWithCalculatedSeverity($userDietaryProfile, $validated['dietary_restrictions'] ?? []);

            // Reload the profile with relationships to verify data
            $userDietaryProfile->load(['medicalConditions', 'dietaryRestrictions']);
            Log::info('Profile after update', [
                'profile_id' => $userDietaryProfile->id,
                'medical_conditions_count' => $userDietaryProfile->medicalConditions->count(),
                'dietary_restrictions_count' => $userDietaryProfile->dietaryRestrictions->count()
            ]);

            // Return to the dashboard instead of the dietary-profile index
            return redirect()->route('dashboard')
                ->with('success', 'Dietary profile updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating dietary profile', [
                'profile_id' => $userDietaryProfile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update dietary profile: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserDietaryProfile $userDietaryProfile)
    {
        $this->authorize('delete', $userDietaryProfile);
        
        $userDietaryProfile->dietaryRestrictions()->detach();
        $userDietaryProfile->delete();
        
        return redirect()->route('dietary-profile.index')
            ->with('message', 'Dietary profile deleted successfully.');
    }

    /**
     * Set a specific dietary profile as active
     * 
     * @param UserDietaryProfile $userDietaryProfile
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setActive(UserDietaryProfile $userDietaryProfile)
    {
        $this->authorize('update', $userDietaryProfile);
        
        // Set all other profiles as inactive
        Auth::user()->dietaryProfiles()->update(['is_active' => false]);
        
        // Set this profile as active
        $userDietaryProfile->is_active = true;
        $userDietaryProfile->save();
        
        return redirect()->back()
            ->with('success', 'Active dietary profile updated successfully.');
    }

    /**
     * Calculate and sync dietary restrictions with severity derived from medical conditions
     * 
     * @param UserDietaryProfile $profile
     * @param array $dietaryRestrictions
     */
    private function syncDietaryRestrictionsWithCalculatedSeverity(UserDietaryProfile $profile, array $dietaryRestrictions)
    {
        // First, load the medical conditions with their severity
        $profile->load('medicalConditions');
        $medicalConditions = $profile->medicalConditions;
        
        $dietaryRestrictionData = [];
        
        foreach ($dietaryRestrictions as $restriction) {
            // Get the highest severity from related medical conditions
            // In a real app, you'd have a mapping between medical conditions and dietary restrictions
            // For now, we'll use the highest severity from any medical condition as a simplification
            $calculatedSeverity = $this->calculateRestrictionSeverity($medicalConditions);
            
            $dietaryRestrictionData[$restriction['id']] = [
                'severity' => $calculatedSeverity,
                'notes' => $restriction['notes'] ?? null
            ];
            
            Log::info("Set dietary restriction {$restriction['id']} severity to {$calculatedSeverity}");
        }
        
        $profile->dietaryRestrictions()->sync($dietaryRestrictionData);
    }
    
    /**
     * Calculate the appropriate severity for a dietary restriction
     * based on related medical conditions
     * 
     * @param \Illuminate\Database\Eloquent\Collection $medicalConditions
     * @return string
     */
    private function calculateRestrictionSeverity($medicalConditions)
    {
        // Simple algorithm: take the highest severity from any medical condition
        // In a real app, this would be more sophisticated based on specific condition-restriction relationships
        $severityMapping = [
            'mild' => 1,
            'moderate' => 2,
            'severe' => 3
        ];
        
        $highestSeverity = 'mild'; // default
        $highestValue = 0;
        
        foreach ($medicalConditions as $condition) {
            $severityValue = $severityMapping[$condition->pivot->severity] ?? 0;
            if ($severityValue > $highestValue) {
                $highestValue = $severityValue;
                $highestSeverity = $condition->pivot->severity;
            }
        }
        
        return $highestSeverity;
    }
}
