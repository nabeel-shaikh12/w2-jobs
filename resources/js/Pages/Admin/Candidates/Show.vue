<template>
  <main class="container flex-grow p-4 sm:p-6">
    <HeaderSegment :title="'Candidate details'" :segments="segments" :buttons="buttons" />
    <div class="space-y-6">
      <Overview v-if="candidateStats.length" :items="candidateStats" />
      <div v-else>Loading candidate stats...</div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <section class="col-span-1 flex h-min w-full flex-col gap-6 lg:sticky lg:top-20">
          <div class="card">
            <div class="card-body flex flex-col items-center">
              <div class="relative my-2 h-24 w-24 rounded-full">
                <img :src="candidate?.avatar" @error="this.src = '/images/avatar1.png'" alt="avatar-img"
                  class="h-full w-full rounded-full" />
              </div>
              <div>
                <h2 class="text-[16px] font-medium text-slate-700 dark:text-slate-200 mb-4">
                  {{ candidate?.name || 'Loading...' }}
                </h2>
                <span class="description">{{ trans('Status') }}: </span>
                <span :class="candidate?.status == 1 ? 'badge badge-success badge-sm' : 'badge badge-danger badge-sm'">
                  <small>{{ candidate?.status == 1 ? 'Active' : 'Suspended' }}</small>
                </span>
              </div>
              <div class="text-center">
                <div>
                  <i class="mr-2"></i>{{ trans('Join Date: ') }} {{ candidate?.created_at_date || 'Loading...' }}
                </div>
              </div>
            </div>
          </div>
        </section>
        <section class="col-span-3">
          <div class="flex gap-4 h-full flex-wrap">
            <div class="flex-grow">
              <div class="card h-full">
                <div class="card-body">
                  <small class="text-muted font-bold">{{ trans('Bio') }}</small>
                  <p>{{ trans('Name : ') }} {{ candidate.name }}</p>
                  <p>{{ trans('Email : ') }} {{ candidate.email }}</p>
                  <p>{{ trans('Phone : ') }} {{ candidate.phone }}</p>
                  <p>{{ trans('Address : ') }} {{ candidate.address }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
        <h4 class="text-xl font-semibold mb-4">{{ trans('Resume Attachment') }}</h4>
        <div class="mb-4">
          <label class="block text-gray-700 mb-2">{{ trans('CV Attachment') }} *</label>
          <div class="flex items-center justify-between p-2 border border-gray-300 rounded-lg bg-gray-50">
            <span v-if="resume" class="text-blue-600 break-all" style="color:black">
              {{ resume }}
            </span>
            <span v-else class="text-gray-500">{{ trans('No CV found') }}</span>
            <button v-if="resume" @click.prevent="removeResume" class="text-gray-500 hover:text-red-600">
              <i class="bi bi-x-circle text-xl"></i>
            </button>
          </div>
        </div>


        <div class="relative inline-block">
          <label for="upload-cv"
            class="flex items-center justify-center gap-2 py-2 px-4 text-white bg-green-600 hover:bg-green-500 rounded-md cursor-pointer transition-all">
            <i class="bi bi-plus text-lg"></i> {{ trans('Upload CV') }}
          </label>
          <input id="upload-cv" type="file" @change="uploadCV($event.target.files[0])"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
        </div>
        <small class="block mt-2 text-gray-500">{{ trans('Upload file') }} .pdf, .doc</small>
        <InputFieldError :message="form.errors.resume" />
      </div>
      <section class="col-span-3">
      </section>
    </div>
  </main>
</template>

<script setup>
import AdminLayout from '@/Layouts/Admin.vue';
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import HeaderSegment from '@/Layouts/Admin/HeaderSegment.vue';
import Overview from '@/Components/Admin/OverviewGrid.vue';
import InputFieldError from '@/Components/InputFieldError.vue';
import trans from '@/Composables/transComposable';
import notify from '@/Plugins/Admin/notify';

const props = defineProps(['candidate', 'segments', 'buttons', 'total_visitors', 'total_shortlisted', 'total_bookmarks', 'total_applied_jobs']);
defineOptions({ layout: AdminLayout });

const candidateStats = [
  { value: props.total_visitors, title: trans('Visits'), iconClass: 'bx bx-box' },
  { value: props.total_shortlisted, title: trans('Shortlisted'), iconClass: 'bx bx-dollar-circle' },
  { value: props.total_bookmarks, title: trans('Bookmarks'), iconClass: 'ti ti-thumb-up' },
  { value: props.total_applied_jobs, title: trans('Applied Jobs'), iconClass: 'ti ti-thumb-up' },
];

const resume = ref(null);
const resume_url = ref(null);

onMounted(() => {
  resume.value = props.candidate?.meta?.resume || null;
  resume_url.value = resume.value ? `/storage/${resume.value}` : null;
});
const form = useForm({
  resume: null,
});
const uploadCV = (file) => {
  if (file) {
    form.resume = file;
    form.post(route('admin.candidate.uploadResume', { candidate: props.candidate.id }), {
      onSuccess: (response) => {
        resume.value = response.resume_url || `/storage/${form.resume.name}`;
        resume_url.value = resume.value;
        form.reset('resume');
        notify.success(trans('Resume uploaded successfully.'));
      },
      onError: (errors) => {
        notify.danger(trans('Something went wrong.'));
        console.error(errors);
      },
    });
  }
};
const removeResume = () => {
  form.post(route('admin.candidate.destroyResume', { candidate: props.candidate.id }), {
    onSuccess: () => {
      resume.value = null;
      resume_url.value = null;
      notify.success(trans('Resume removed successfully.'));
    },
    onError: (errors) => {
      notify.danger(trans('Something went wrong.'));
      console.error(errors);
    },
  });
};
</script>
