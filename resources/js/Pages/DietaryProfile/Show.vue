<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    profile: Object,
});

const getSeverityColor = (severity) => {
    switch(severity) {
        case 'mild': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'moderate': return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100';
        case 'severe': return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    }).format(date);
};
</script>

<template>
    <AppLayout title="View Dietary Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    View Dietary Profile
                </h2>
                <div class="flex space-x-3">
                    <Link 
                        :href="route('dietary-profile.edit', props.profile.id)" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                    >
                        Edit Profile
                    </Link>
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
                <!-- Profile Header -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="flex flex-col">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ props.profile.name }}
                                </h3>
                                <p v-if="props.profile.description" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ props.profile.description }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Created: {{ formatDate(props.profile.created_at) }} | 
                                    Updated: {{ formatDate(props.profile.updated_at) }}
                                </p>
                            </div>
                            <div>
                                <span v-if="props.profile.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100">
                                    Active Profile
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Medical Conditions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Medical Conditions
                        </h3>
                        <div v-if="props.profile.medical_conditions && props.profile.medical_conditions.length > 0" class="space-y-3">
                            <div v-for="condition in props.profile.medical_conditions" :key="condition.id" class="p-3 border border-gray-200 dark:border-gray-700 rounded-md">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ condition.name }}</h4>
                                        <p v-if="condition.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ condition.description }}
                                        </p>
                                    </div>
                                    <span :class="[getSeverityColor(condition.pivot.severity), 'px-2 py-1 rounded-md text-xs font-medium capitalize']">
                                        {{ condition.pivot.severity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-gray-500 dark:text-gray-400">
                                No medical conditions added to this profile
                            </p>
                        </div>
                    </div>

                    <!-- Dietary Restrictions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Dietary Restrictions
                        </h3>
                        <div v-if="props.profile.dietary_restrictions && props.profile.dietary_restrictions.length > 0" class="space-y-3">
                            <div v-for="restriction in props.profile.dietary_restrictions" :key="restriction.id" class="p-3 border border-gray-200 dark:border-gray-700 rounded-md">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ restriction.name }}</h4>
                                        <p v-if="restriction.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ restriction.description }}
                                        </p>
                                        <p v-if="restriction.pivot.notes" class="mt-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 p-2 rounded">
                                            <span class="font-medium">Notes:</span> {{ restriction.pivot.notes }}
                                        </p>
                                    </div>
                                    <span :class="[getSeverityColor(restriction.pivot.severity), 'px-2 py-1 rounded-md text-xs font-medium capitalize']">
                                        {{ restriction.pivot.severity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-gray-500 dark:text-gray-400">
                                No dietary restrictions added to this profile
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
