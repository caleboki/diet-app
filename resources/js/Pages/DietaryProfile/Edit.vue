<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    profile: Object,
    medicalConditions: Array,
    commonDietaryRestrictions: Array,
});

// Simple direct form
const form = useForm({
    name: props.profile?.name || '',
    description: props.profile?.description || '',
    medical_conditions: props.profile?.medical_conditions?.map(condition => ({
        id: condition.id,
        severity: condition.pivot?.severity || 'mild'
    })) || [],
    dietary_restrictions: props.profile?.dietary_restrictions?.map(restriction => ({
        id: restriction.id,
        name: restriction.name,
        severity: restriction.pivot?.severity || 'mild',
        notes: restriction.pivot?.notes || ''
    })) || []
});

// Computed property to filter out already selected conditions
const availableMedicalConditions = computed(() => {
    const selectedIds = form.medical_conditions.map(c => c.id);
    return props.medicalConditions.filter(c => !selectedIds.includes(c.id));
});

// Computed property to filter available dietary restrictions
const availableDietaryRestrictions = computed(() => {
    const selectedIds = form.dietary_restrictions.map(r => r.id);
    return props.commonDietaryRestrictions.filter(r => !selectedIds.includes(r.id));
});

const severityOptions = [
    { value: 'mild', label: 'Mild' },
    { value: 'moderate', label: 'Moderate' },
    { value: 'severe', label: 'Severe' },
];

// Function to add a medical condition
const addMedicalCondition = () => {
    if (!selectedConditionId.value) return;
    
    const condition = props.medicalConditions.find(c => c.id === Number(selectedConditionId.value));
    if (condition) {
        // Create a completely new array
        const updatedConditions = [...form.medical_conditions];
        updatedConditions.push({
            id: condition.id,
            severity: 'moderate' // Default severity
        });
        
        // Set the form data with the new array
        form.medical_conditions = updatedConditions;
        
        // Reset the selection
        selectedConditionId.value = '';
        
        // Update recommended dietary restrictions
        updateRecommendedRestrictions();
    }
};

// Function to remove a medical condition
const removeMedicalCondition = (index) => {
    // Create a completely new array
    const updatedConditions = [...form.medical_conditions];
    updatedConditions.splice(index, 1);
    form.medical_conditions = updatedConditions;
    
    // Update recommended restrictions when removing a condition
    updateRecommendedRestrictions();
};

// Get recommended dietary restrictions based on selected medical conditions
const updateRecommendedRestrictions = () => {
    // If no medical conditions are selected, don't do anything
    if (form.medical_conditions.length === 0) {
        return;
    }
    
    // Get a copy of all user manually selected restrictions to preserve them
    const userManualSelections = [...form.dietary_restrictions];
    
    // Get condition IDs from selected conditions
    const conditionIds = form.medical_conditions.map(condition => condition.id);
    
    // Call the backend API to get recommended restrictions
    axios.post(route('medical-conditions.recommended-restrictions'), {
        condition_ids: conditionIds
    }).then(response => {
        if (response.data.success) {
            // Create a new array to ensure reactivity
            let updatedRestrictions = [];
            
            // Process recommendations from the API
            response.data.recommendations.forEach(recommendation => {
                // Check if this was previously selected (to preserve notes & user selections)
                const previousSelection = userManualSelections.find(r => 
                    // Handle both temporary and permanent IDs
                    (typeof r.id === 'string' && typeof recommendation.id === 'string' && 
                     r.id === recommendation.id) || 
                    (typeof r.id === 'number' && typeof recommendation.id === 'number' && 
                     r.id === recommendation.id)
                );
                
                // If it was previously selected OR it's a new recommendation, include it
                if (previousSelection || !userManualSelections.some(r => r.id === recommendation.id)) {
                    // Add to updatedRestrictions, preserving notes if existed
                    updatedRestrictions.push({
                        id: recommendation.id,
                        name: recommendation.name,
                        description: recommendation.description,
                        severity: previousSelection ? previousSelection.severity : recommendation.recommended_severity,
                        notes: previousSelection ? previousSelection.notes : '',
                        source_condition: recommendation.source_condition,
                        is_recommended: true,
                        is_ai_generated: recommendation.is_ai_generated || false,
                        is_temporary: recommendation.is_temporary || false
                    });
                }
            });
            
            // Add any manually selected restrictions that weren't in the recommendations
            userManualSelections.forEach(restriction => {
                if (!updatedRestrictions.some(r => r.id === restriction.id)) {
                    updatedRestrictions.push({
                        ...restriction,
                        is_recommended: false
                    });
                }
            });
            
            // Set the form data with the new array to ensure reactivity
            form.dietary_restrictions = updatedRestrictions;
        }
    }).catch(error => {
        console.error('Error fetching recommended restrictions:', error);
    });
};

// Function to add a dietary restriction
const addDietaryRestriction = () => {
    if (!selectedDietaryRestrictionId.value) return;
    
    const restriction = props.commonDietaryRestrictions.find(r => r.id === Number(selectedDietaryRestrictionId.value));
    if (restriction) {
        // Create a completely new array to ensure reactivity
        const updatedRestrictions = [...form.dietary_restrictions];
        updatedRestrictions.push({
            id: restriction.id,
            name: restriction.name,
            severity: 'moderate', // Fixed severity since it's derived from medical conditions
            notes: ''
        });
        
        // Set the form data with the new array
        form.dietary_restrictions = updatedRestrictions;
        
        // Reset the selection
        selectedDietaryRestrictionId.value = '';
    }
};

// Function to remove a dietary restriction
const removeDietaryRestriction = (index) => {
    // Create a completely new array to ensure reactivity
    const updatedRestrictions = [...form.dietary_restrictions];
    updatedRestrictions.splice(index, 1);
    form.dietary_restrictions = updatedRestrictions;
};

// Function to submit the form
const submit = () => {
    form.put(route('dietary-profile.update', props.profile.id), {
        onSuccess: () => {
            // Redirect or show success message
        }
    });
};

// For condition selection
const selectedConditionId = ref('');
const selectedDietaryRestrictionId = ref('');
</script>

<template>
    <AppLayout title="Edit Dietary Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Dietary Profile
                </h2>
                <Link
                    :href="route('dietary-profile.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-300 dark:active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                    Back to Profiles
                </Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Profile Details
                        </h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="col-span-1">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div class="col-span-1">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medical Conditions Section -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Medical Conditions
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Add conditions that may affect your diet
                            </span>
                        </div>
                        
                        <!-- Add Medical Condition Form -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Medical Condition</label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <select 
                                        v-model="selectedConditionId" 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Select a condition</option>
                                        <option v-for="condition in availableMedicalConditions" :key="condition.id" :value="condition.id">
                                            {{ condition.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <button 
                                        type="button" 
                                        @click="addMedicalCondition"
                                        :disabled="!selectedConditionId" 
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300 disabled:cursor-not-allowed"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selected Medical Conditions -->
                        <div v-if="form.medical_conditions.length > 0" class="space-y-3">
                            <div 
                                v-for="(condition, index) in form.medical_conditions" 
                                :key="index"
                                class="flex items-center space-x-4 p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ props.medicalConditions.find(c => c.id === condition.id)?.name }}
                                        </span>
                                        <span 
                                            v-if="props.medicalConditions.find(c => c.id === condition.id)?.is_custom"
                                            class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                        >
                                            Custom
                                        </span>
                                    </div>
                                </div>
                                <div class="w-40">
                                    <select 
                                        v-model="form.medical_conditions[index].severity"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                                <button 
                                    type="button"
                                    @click="removeMedicalCondition(index)"
                                    class="inline-flex items-center p-1.5 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-700 rounded-md">
                            No medical conditions selected
                        </div>
                    </div>
                    
                    <!-- Dietary Restrictions Section -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Dietary Restrictions
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Add food restrictions, allergies or preferences
                            </span>
                        </div>
                        
                        <!-- Add Dietary Restriction Form -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Dietary Restriction</label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <select 
                                        v-model="selectedDietaryRestrictionId" 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Select a restriction</option>
                                        <option v-for="restriction in availableDietaryRestrictions" :key="restriction.id" :value="restriction.id">
                                            {{ restriction.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <button 
                                        type="button" 
                                        @click="addDietaryRestriction"
                                        :disabled="!selectedDietaryRestrictionId" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selected Dietary Restrictions -->
                        <div v-if="form.dietary_restrictions.length > 0" class="space-y-3">
                            <div 
                                v-for="(restriction, index) in form.dietary_restrictions" 
                                :key="index"
                                class="flex items-center space-x-4 p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ restriction.name }}
                                        </span>
                                        <span 
                                            v-if="restriction.is_recommended" 
                                            class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100"
                                            title="Recommended based on your selected medical conditions"
                                        >
                                            Recommended
                                        </span>
                                        <span 
                                            v-if="restriction.is_ai_generated" 
                                            class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                            title="AI-enhanced recommendation based on current knowledge"
                                        >
                                            AI Enhanced
                                        </span>
                                        <span 
                                            v-if="restriction.source_condition"
                                            class="ml-2 text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            (from {{ restriction.source_condition.name }})
                                        </span>
                                    </div>
                                </div>
                                <div class="w-64">
                                    <input 
                                        v-model="form.dietary_restrictions[index].notes"
                                        type="text"
                                        placeholder="Add notes about this restriction"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <button 
                                    type="button"
                                    @click="removeDietaryRestriction(index)"
                                    class="inline-flex items-center p-1.5 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-700 rounded-md">
                            No dietary restrictions selected
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
