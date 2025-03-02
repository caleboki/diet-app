<?php

namespace App\Http\Controllers;

use App\Models\DietaryRestriction;
use Illuminate\Http\Request;

class DietaryRestrictionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = $request->input('query');
        $isCommonAllergen = $request->boolean('is_common_allergen');
        $medicalConditionId = $request->input('medical_condition_id');
        
        $restrictions = DietaryRestriction::when($query, function ($q) use ($query) {
                return $q->where('name', 'like', "%{$query}%");
            })
            ->when($isCommonAllergen !== null, function ($q) use ($isCommonAllergen) {
                return $q->where('is_common_allergen', $isCommonAllergen);
            })
            ->when($medicalConditionId, function ($q) use ($medicalConditionId) {
                return $q->where('medical_condition_id', $medicalConditionId);
            })
            ->with('medicalCondition')
            ->orderBy('name')
            ->get();
            
        return response()->json($restrictions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DietaryRestriction $dietaryRestriction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DietaryRestriction $dietaryRestriction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DietaryRestriction $dietaryRestriction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DietaryRestriction $dietaryRestriction)
    {
        //
    }
}
