<template>
  <div class="custom-condition-form">
    <h3 class="text-lg font-medium mb-4">Add Custom Medical Condition</h3>
    
    <form @submit.prevent="submitForm">
      <div class="mb-4">
        <InputLabel for="name" value="Condition Name" />
        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
          :disabled="form.processing"
        />
        <InputError :message="form.errors.name" class="mt-2" />
      </div>
      
      <div class="mb-4">
        <InputLabel for="description" value="Description" />
        <Textarea
          id="description"
          v-model="form.description"
          class="mt-1 block w-full"
          required
          :disabled="form.processing"
        />
        <InputError :message="form.errors.description" class="mt-2" />
        <p class="mt-1 text-sm text-gray-500">
          Please provide details about this condition and how it affects dietary needs.
        </p>
      </div>
      
      <div class="flex items-center justify-end mt-4">
        <PrimaryButton class="ml-4" :disabled="form.processing">
          Create Condition
        </PrimaryButton>
      </div>
    </form>

    <div v-if="successMessage" class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded">
      {{ successMessage }}
    </div>
    
    <div v-if="duplicateCondition" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded">
      <p class="font-medium">Similar condition already exists:</p>
      <p class="mt-2">{{ duplicateCondition.name }}</p>
      <p class="mt-1 text-sm">{{ duplicateCondition.description }}</p>
      <p v-if="duplicateCondition.is_custom" class="mt-2 text-xs italic">
        This is a custom condition you created
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import axios from 'axios';

console.log('CustomMedicalConditionForm script executing');

onMounted(() => {
  console.log('CustomMedicalConditionForm component mounted');
});

const props = defineProps({
  onConditionCreated: {
    type: Function,
    default: () => {}
  }
});

const form = useForm({
  name: '',
  description: ''
});

const successMessage = ref('');
const duplicateCondition = ref(null);

const submitForm = async () => {
  try {
    const response = await axios.post('/medical-conditions', form);
    
    if (response.data.status === 'duplicate') {
      duplicateCondition.value = response.data.condition;
      successMessage.value = '';
    } else {
      form.reset();
      successMessage.value = 'Medical condition created successfully!';
      duplicateCondition.value = null;
      
      // Emit event with the new condition
      props.onConditionCreated(response.data.condition);
    }
  } catch (error) {
    if (error.response && error.response.data.errors) {
      form.setError('name', error.response.data.errors.name);
      form.setError('description', error.response.data.errors.description);
    }
  }
};
</script>
