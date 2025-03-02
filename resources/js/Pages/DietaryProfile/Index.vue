<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    profiles: Array,
});

const confirmingProfileDeletion = ref(false);
const profileToDelete = ref(null);

const confirmProfileDeletion = (profile) => {
    profileToDelete.value = profile;
    confirmingProfileDeletion.value = true;
};

const deleteProfile = () => {
    router.delete(route('dietary-profile.destroy', profileToDelete.value.id), {
        onSuccess: () => {
            confirmingProfileDeletion.value = false;
            profileToDelete.value = null;
        },
    });
};

const setActiveProfile = (profile) => {
    if (profile.is_active) return;
    
    router.put(route('dietary-profile.update', profile.id), {
        profile_name: profile.profile_name,
        is_active: true,
    });
};
</script>

<template>
    <AppLayout title="Dietary Profiles">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Dietary Profiles
                </h2>
                <Link 
                    :href="route('dietary-profile.create')" 
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Create New Profile
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.message" class="mb-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-emerald-50 dark:bg-emerald-900 border-l-4 border-emerald-500 text-emerald-700 dark:text-emerald-200">
                        <p>{{ $page.props.flash.message }}</p>
                    </div>
                </div>

                <div v-if="profiles.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 text-center">
                        <p class="mb-4">You don't have any dietary profiles yet.</p>
                        <Link 
                            :href="route('dietary-profile.create')" 
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Create Your First Profile
                        </Link>
                    </div>
                </div>

                <div v-else class="space-y-6">
                    <div 
                        v-for="profile in profiles" 
                        :key="profile.id" 
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                        :class="{ 'ring-2 ring-emerald-500': profile.is_active }"
                    >
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                                <div>
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            {{ profile.name }}
                                        </h3>
                                        <span 
                                            v-if="profile.is_active" 
                                            class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100"
                                        >
                                            Active
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ profile.medical_conditions.length }} medical conditions · 
                                        {{ profile.dietary_restrictions.length }} dietary restrictions
                                    </p>
                                </div>
                                
                                <div class="mt-4 md:mt-0 flex space-x-3">
                                    <Link 
                                        :href="route('dietary-profile.show', profile.id)" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-800 hover:bg-emerald-200 dark:hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                    >
                                        View
                                    </Link>
                                    <Link 
                                        :href="route('dietary-profile.edit', profile.id)" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="!profile.is_active"
                                        @click="setActiveProfile(profile)"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                    >
                                        Set as Active
                                    </button>
                                    <button
                                        @click="confirmProfileDeletion(profile)"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="profile.medical_conditions.length > 0" class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Medical Conditions:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="condition in profile.medical_conditions" 
                                        :key="condition.id"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                    >
                                        {{ condition.name }}
                                        <span v-if="condition.pivot.severity" class="ml-1 opacity-75">
                                            ({{ condition.pivot.severity }})
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            <div v-if="profile.dietary_restrictions.length > 0" class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dietary Restrictions:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="restriction in profile.dietary_restrictions" 
                                        :key="restriction.id"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100"
                                    >
                                        {{ restriction.name }}
                                        <span v-if="restriction.pivot.severity" class="ml-1 opacity-75">
                                            ({{ restriction.pivot.severity }})
                                        </span>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic">
                                    Restriction severity derived from medical condition severity
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delete Profile Confirmation Modal -->
        <Modal :show="confirmingProfileDeletion" @close="confirmingProfileDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Delete Dietary Profile
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete this dietary profile? This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="confirmingProfileDeletion = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ml-3"
                        @click="deleteProfile"
                    >
                        Delete Profile
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
