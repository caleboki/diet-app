<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    profile: Object,
    medicalConditions: Array,
    commonDietaryRestrictions: Array,
});

const form = useForm({
    name: props.profile.name,
    description: props.profile.description || '',
    medical_conditions: props.profile.medical_conditions.map(condition => ({
        id: condition.id,
        severity: condition.pivot.severity
    })),
    dietary_restrictions: props.profile.dietary_restrictions.map(restriction => ({
        id: restriction.id,
        notes: restriction.pivot.notes || ''
    })),
});

const availableMedicalConditions = computed(() => {
    // Filter out conditions already selected
    const selectedIds = form.medical_conditions.map(c => c.id);
    return props.medicalConditions.filter(c => !selectedIds.includes(c.id));
});

const availableDietaryRestrictions = computed(() => {
    // Filter out restrictions already selected
    const selectedIds = form.dietary_restrictions.map(r => r.id);
    return props.commonDietaryRestrictions.filter(r => !selectedIds.includes(r.id));
});

const severityOptions = [
    { value: 'mild', label: 'Mild' },
    { value: 'moderate', label: 'Moderate' },
    { value: 'severe', label: 'Severe' },
];

const addMedicalCondition = (conditionId) => {
    const condition = props.medicalConditions.find(c => c.id === conditionId);
    if (condition) {
        form.medical_conditions.push({
            id: condition.id,
            severity: 'mild'
        });
    }
};

const removeMedicalCondition = (index) => {
    form.medical_conditions.splice(index, 1);
};

const addDietaryRestriction = (restrictionId) => {
    const restriction = props.commonDietaryRestrictions.find(r => r.id === restrictionId);
    if (restriction) {
        form.dietary_restrictions.push({
            id: restriction.id,
            notes: ''
        });
    }
};

const removeDietaryRestriction = (index) => {
    form.dietary_restrictions.splice(index, 1);
};

const submit = () => {
    form.put(route('dietary-profile.update', props.profile.id), {
        onSuccess: () => {
            // Reset the form
            form.reset();
        },
    });
};

const conditionToAdd = ref('');
const restrictionToAdd = ref('');
</script>

<template>
    <AppLayout title="Edit Dietary Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Dietary Profile
                </h2>
                <div>
                    <Link 
                        :href="route('dashboard')" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Profile Details
                        </h3>
                        <div class="grid grid-cols-1 gap-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Name</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (Optional)</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Medical Conditions
                        </h3>
                        
                        <!-- Add Medical Condition -->
                        <div class="mb-6">
                            <label for="add-condition" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Medical Condition</label>
                            <div class="flex space-x-3">
                                <select
                                    id="add-condition"
                                    v-model="conditionToAdd"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                >
                                    <option value="">Select a condition</option>
                                    <option v-for="condition in availableMedicalConditions" :key="condition.id" :value="condition.id">
                                        {{ condition.name }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="addMedicalCondition(conditionToAdd); conditionToAdd = '';"
                                    :disabled="!conditionToAdd"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Add
                                </button>
                            </div>
                        </div>
                        
                        <!-- Selected Medical Conditions -->
                        <div v-if="form.medical_conditions.length > 0">
                            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Selected Conditions</h4>
                            <div class="space-y-3">
                                <div 
                                    v-for="(condition, index) in form.medical_conditions" 
                                    :key="index"
                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ props.medicalConditions.find(c => c.id === condition.id)?.name }}
                                            </div>
                                        </div>
                                        <div class="w-32">
                                            <select 
                                                v-model="condition.severity"
                                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                            >
                                                <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="removeMedicalCondition(index)"
                                            class="text-red-500 hover:text-red-700 focus:outline-none"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No medical conditions selected
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Dietary Restrictions
                        </h3>
                        
                        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-500 text-blue-700 dark:text-blue-200">
                            <p>The severity of dietary restrictions is automatically determined based on the severity of your medical conditions.</p>
                        </div>
                        
                        <!-- Add Dietary Restriction -->
                        <div class="mb-6">
                            <label for="add-restriction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Dietary Restriction</label>
                            <div class="flex space-x-3">
                                <select
                                    id="add-restriction"
                                    v-model="restrictionToAdd"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                >
                                    <option value="">Select a restriction</option>
                                    <option v-for="restriction in availableDietaryRestrictions" :key="restriction.id" :value="restriction.id">
                                        {{ restriction.name }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="addDietaryRestriction(restrictionToAdd); restrictionToAdd = '';"
                                    :disabled="!restrictionToAdd"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Add
                                </button>
                            </div>
                        </div>
                        
                        <!-- Selected Dietary Restrictions -->
                        <div v-if="form.dietary_restrictions.length > 0">
                            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Selected Restrictions</h4>
                            <div class="space-y-3">
                                <div 
                                    v-for="(restriction, index) in form.dietary_restrictions" 
                                    :key="index"
                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                >
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ props.commonDietaryRestrictions.find(r => r.id === restriction.id)?.name }}
                                            </div>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="removeDietaryRestriction(index)"
                                            class="text-red-500 hover:text-red-700 focus:outline-none"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <div>
                                        <label :for="`restriction-notes-${index}`" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
                                        <textarea 
                                            :id="`restriction-notes-${index}`" 
                                            v-model="restriction.notes" 
                                            rows="2" 
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No dietary restrictions selected
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end mt-6">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            :disabled="form.processing"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
