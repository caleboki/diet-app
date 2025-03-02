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

class UserDietaryProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = auth()->user()->dietaryProfiles()
            ->with(['dietaryRestrictions', 'medicalConditions'])
            ->get();
        
        return Inertia::render('DietaryProfile/Index', [
            'profiles' => $profiles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $medicalConditions = MedicalCondition::orderBy('name')->get();
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
            'dietary_restrictions.*.severity' => 'required|in:mild,moderate,severe',
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

        // Sync dietary restrictions with severity and notes
        $dietaryRestrictionData = [];
        foreach ($validated['dietary_restrictions'] ?? [] as $restriction) {
            $dietaryRestrictionData[$restriction['id']] = [
                'severity' => $restriction['severity'],
                'notes' => $restriction['notes'] ?? null
            ];
        }
        $profile->dietaryRestrictions()->sync($dietaryRestrictionData);

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
        
        $userDietaryProfile->load('dietaryRestrictions', 'medicalConditions', 'user');
        
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
        
        $userDietaryProfile->load('dietaryRestrictions', 'medicalConditions');
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
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'medical_conditions' => 'array',
            'medical_conditions.*.id' => 'required|exists:medical_conditions,id',
            'medical_conditions.*.severity' => 'required|in:mild,moderate,severe',
            'dietary_restrictions' => 'array',
            'dietary_restrictions.*.id' => 'required|exists:dietary_restrictions,id',
            'dietary_restrictions.*.severity' => 'required|in:mild,moderate,severe',
            'dietary_restrictions.*.notes' => 'nullable|string|max:1000',
        ]);

        // Update profile
        $userDietaryProfile->name = $validated['name'];
        $userDietaryProfile->description = $validated['description'] ?? null;
        $userDietaryProfile->save();

        // Sync medical conditions with severity
        $medicalConditionData = [];
        foreach ($validated['medical_conditions'] ?? [] as $condition) {
            $medicalConditionData[$condition['id']] = ['severity' => $condition['severity']];
        }
        $userDietaryProfile->medicalConditions()->sync($medicalConditionData);

        // Sync dietary restrictions with severity and notes
        $dietaryRestrictionData = [];
        foreach ($validated['dietary_restrictions'] ?? [] as $restriction) {
            $dietaryRestrictionData[$restriction['id']] = [
                'severity' => $restriction['severity'],
                'notes' => $restriction['notes'] ?? null
            ];
        }
        $userDietaryProfile->dietaryRestrictions()->sync($dietaryRestrictionData);

        // Return to the dashboard instead of the dietary-profile index
        return redirect()->route('dashboard')
            ->with('success', 'Dietary profile updated successfully.');
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
}
