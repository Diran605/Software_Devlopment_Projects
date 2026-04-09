<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/50 backdrop-blur-sm overflow-y-auto" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all w-full max-w-2xl border border-gray-100 flex flex-col max-h-[90vh]">
        <div class="px-6 pt-6 pb-4 overflow-y-auto">
            <h3 class="text-xl leading-6 font-semibold text-gray-900" id="modal-title">
                {{ isEditing ? 'Edit Activity Log' : 'Log New Activity' }}
              </h3>
              
              <form @submit.prevent="submitForm" class="mt-6 space-y-4">
                <div v-if="error" class="text-sm text-red-600 bg-red-50 p-3 rounded-lg border border-red-100">
                    {{ error }}
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" v-model="form.date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    </div>
                </div>

                <!-- Link to Plan Toggle -->
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-sm font-medium text-gray-900 border-b-2 border-transparent pr-2">Is this a planned task?</label>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-500">No</span>
                            <button type="button" @click="form.is_planned = !form.is_planned" 
                                    :class="form.is_planned ? 'bg-indigo-600' : 'bg-gray-200'" 
                                    class="relative inline-flex shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span aria-hidden="true" :class="form.is_planned ? 'translate-x-5' : 'translate-x-0'" 
                                      class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                            </button>
                            <span class="text-sm text-gray-500">Yes</span>
                        </div>
                    </div>
                    
                    <div v-if="form.is_planned" class="mt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Daily Plan</label>
                        <select v-model="form.daily_plan_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" required>
                            <option value="" disabled>Select a plan from today...</option>
                            <option v-for="plan in availablePlans" :key="plan.id" :value="plan.id">
                                {{ plan.task_name }} ({{ plan.project_client || 'No Project' }})
                            </option>
                        </select>
                        <p v-if="availablePlans.length === 0" class="text-xs text-orange-600 mt-1">
                            No plans found for the selected date. Please ensure you have created a plan first.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Name</label>
                    <input type="text" v-model="form.task_name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                           placeholder="What exactly did you do?">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Type</label>
                        <select v-model="form.work_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                            <option value="direct">Direct (Client Billable)</option>
                            <option value="indirect">Indirect (Admin/Internal)</option>
                            <option value="growth">Growth (Training/RnD)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Completion Level</label>
                        <select v-model="form.completion_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                            <option value="complete">Complete</option>
                            <option value="partial">Partial</option>
                            <option value="attempted">Attempted / Blocked</option>
                        </select>
                    </div>
                </div>

                <!-- Time tracking section -->
                <div class="grid grid-cols-3 gap-4 bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                        <input type="time" v-model="form.start_time"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                        <input type="time" v-model="form.end_time"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (Mins)</label>
                        <input type="number" min="1" v-model="form.duration_minutes" :placeholder="calcDuration"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Output / Results <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <textarea v-model="form.output" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                              placeholder="Briefly describe what was officially delivered or accomplished..."></textarea>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 mt-6 -mx-4 -mb-4 bg-gray-50 rounded-b-2xl">
                    <button type="submit" :disabled="loading"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-70">
                        {{ loading ? 'Saving...' : 'Save Activity Log' }}
                    </button>
                    <button type="button" @click="$emit('close')"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
              </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import api from '../lib/axios';

const props = defineProps({
    logData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.logData);
const loading = ref(false);
const error = ref(null);
const availablePlans = ref([]);

const form = reactive({
    date: new Date().toISOString().split('T')[0],
    task_name: '',
    project_client: '',
    work_type: 'direct',
    start_time: '',
    end_time: '',
    duration_minutes: '',
    output: '',
    completion_type: 'complete',
    is_planned: false,
    daily_plan_id: '',
    notes: ''
});

const calcDuration = computed(() => {
    if(form.start_time && form.end_time) {
        const d1 = new Date(`2000-01-01T${form.start_time}`);
        const d2 = new Date(`2000-01-01T${form.end_time}`);
        let diffMs = d2 - d1;
        if(diffMs < 0) return 'Invalid'; // end before start
        return Math.floor(diffMs / 60000) + ' (Auto)';
    }
    return '';
});

// Fetch plans for linking when date changes
watch(() => form.date, async (newDate) => {
    // Only fetch if they explicitly indicated it's planned, or reset available if not fetching
    if (newDate) {
        try {
            const resp = await api.get('/plans', { params: { date: newDate, per_page: 50 } });
            const payload = resp.data.data || resp.data;
            availablePlans.value = Array.isArray(payload) ? payload : [];
        } catch (e) {
            console.error("Failed loading plans for log mapping", e);
        }
    }
}, { immediate: true });

watch(() => props.logData, (newData) => {
    if (newData) {
        form.date = newData.date;
        form.task_name = newData.task_name;
        form.project_client = newData.project_client || '';
        form.work_type = newData.work_type.value;
        form.start_time = newData.start_time ? newData.start_time.substring(0, 5) : '';
        form.end_time = newData.end_time ? newData.end_time.substring(0, 5) : '';
        form.duration_minutes = newData.duration_minutes || '';
        form.output = newData.output || '';
        form.completion_type = newData.completion_type.value;
        form.is_planned = newData.raw_is_planned;
        form.daily_plan_id = newData.daily_plan_id || '';
        form.notes = newData.notes || '';
    } else {
        form.date = new Date().toISOString().split('T')[0];
        form.task_name = '';
        form.project_client = '';
        form.work_type = 'direct';
        form.start_time = '';
        form.end_time = '';
        form.duration_minutes = '';
        form.output = '';
        form.completion_type = 'complete';
        form.is_planned = false;
        form.daily_plan_id = '';
        form.notes = '';
    }
}, { immediate: true });

// Sync task_name and project_client when daily_plan_id changes (and it's not editing an existing log heavily)
watch(() => form.daily_plan_id, (newPlanId) => {
    if (form.is_planned && newPlanId && !isEditing.value) {
        const plan = availablePlans.value.find(p => p.id === newPlanId);
        if (plan) {
            if(!form.task_name) form.task_name = plan.task_name;
            if(!form.project_client) form.project_client = plan.project_client || '';
        }
    }
});

const submitForm = async () => {
    loading.value = true;
    error.value = null;
    
    // Cleanup empty strings to nulls
    const payload = {...form};
    if(payload.start_time && payload.start_time.split(':').length === 2) payload.start_time += ':00';
    if(payload.end_time && payload.end_time.split(':').length === 2) payload.end_time += ':00';
    
    if(!payload.start_time) delete payload.start_time;
    if(!payload.end_time) delete payload.end_time;
    if(!payload.duration_minutes) delete payload.duration_minutes;
    if(!payload.daily_plan_id || !payload.is_planned) delete payload.daily_plan_id;

    try {
        if (isEditing.value) {
            await api.put(`/logs/${props.logData.id}`, payload);
        } else {
            await api.post(`/logs`, payload);
        }
        emit('saved');
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred while saving the activity log.';
    } finally {
        loading.value = false;
    }
};
</script>
