<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AdminLayout from '@/Layouts/Admin.vue'
import Paginate from '@/Components/Paginate.vue'
import HeaderSegment from '@/Layouts/Admin/HeaderSegment.vue'
import trans from '@/Composables/transComposable'
import Filter from '@/Components/Admin/Filter.vue'
import Overview from '@/Components/Admin/OverviewGrid.vue'
import sharedComposable from '@/Composables/sharedComposable'
import NoDataFound from '@/Components/Admin/NoDataFound.vue'

const { deleteRow } = sharedComposable()

defineOptions({ layout: AdminLayout })

const props = defineProps([
  'segments',
  'buttons',
  'request',
  'candidates',
  'total_candidates',
  'active_candidates',
  'inactive_candidates',
  'verified_candidates'
])

// Candidate statistics
const candidateStats = [
  { value: props.total_candidates, title: trans('Total Candidates'), iconClass: 'bx bx-list-ul' },
  { value: props.active_candidates, title: trans('Active Candidates'), iconClass: 'bx bx-check-shield' },
  { value: props.inactive_candidates, title: trans('Inactive Candidates'), iconClass: 'bx bx-x-circle' },
  { value: props.verified_candidates, title: trans('Verified Candidates'), iconClass: 'bx bx-check-shield' }
]

// Local candidate data to manage updates if necessary
const localCandidates = ref(props.candidates)

// Fetch candidates on component mount if `candidates` is empty
onMounted(async () => {
  if (!props.candidates || props.candidates.total === 0) {
    try {
      const response = await axios.get('/admin/candidates') // Adjust endpoint as needed
      localCandidates.value = response.data
    } catch (error) {
      console.error('Error fetching candidates:', error)
    }
  }
})

// Modal state for bulk upload
const showModal = ref(false)
const files = ref([])

// Input for name and skills
const nameInput = ref('')
const skillsInput = ref('')

// File upload handler
const handleFileUpload = (event) => {
  const selectedFiles = Array.from(event.target.files)
  const validFiles = selectedFiles.filter(file =>
  (file.type === 'application/pdf' || 
  file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
  file.type === 'application/msword')
);


  if (files.value.length + validFiles.length <= 100) {
    files.value = files.value.concat(validFiles)
  } else {
    alert('You can only upload a maximum of 100 resumes.')
  }
}

const uploadResumes = async () => {
  const formData = new FormData()

  files.value.forEach((file, index) => {
    formData.append(`resume[${index}]`, file)
  })

  formData.append('name', nameInput.value) // Append name
  formData.append('skills', skillsInput.value) // Append skills

  try {
    const response = await axios.post('/admin/upload-resumes', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    alert('Resumes uploaded successfully!')
    showModal.value = false
    files.value = []

    // Re-fetch the candidates after upload
    fetchCandidates()
  } catch (error) {
    if (error.response) {
      const errorMessage = error.response.data.message
      if (errorMessage === 'Email not found in document, which is required.') {
        alert('Error: Email not found in the document. Each resume must include an email address.')
      } else {
        alert('Error uploading resumes: ' + errorMessage || 'Unknown error occurred.')
      }
      console.error('Error Response:', error.response.data)
    } else {
      console.error('Error:', error)
      alert('Error uploading resumes. Please try again.')
    }
  }
}

</script>

<template>
  <main class="container flex-grow p-4 sm:p-6">
    <HeaderSegment :title="trans('Candidates')" :segments="segments" :buttons="buttons" />
    <div class="space-y-6">
      <Overview :items="candidateStats" class="lg:grid-cols-3" />

      <!-- Container for Filter and Bulk Upload Button in one row -->
      <div class="flex justify-between items-center mb-4">
        <Filter :request="request" :segments="segments" :buttons="buttons" />

        <!-- Bulk Upload Button -->
        <button @click="showModal = true" style="padding: 10px 20px; background-color: #7C3AED; color: white; border: none; border-radius: 5px;">
          {{ trans('Bulk Upload Resumes') }}
        </button>
      </div>

      <!-- Modal for Resume Upload -->
      <div v-if="showModal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; justify-content: center; align-items: center; background-color: rgba(0, 0, 0, 0.5);">
        <div style="background-color: white; padding: 20px; border-radius: 5px; width: 400px;">
          <span style="cursor: pointer; float: right;" @click="showModal = false">&times;</span>
          <h4>Upload Resumes</h4>

          <!-- Name Input -->
          <input 
            v-model="nameInput" 
            type="text" 
            placeholder="Enter Candidate Name"
            style="margin-top: 10px; margin-bottom: 10px; width: 100%; padding: 8px;"
          />

          <!-- Skills Input -->
          <input 
            v-model="skillsInput" 
            type="text" 
            placeholder="Enter Candidate Skills"
            style="margin-top: 10px; margin-bottom: 10px; width: 100%; padding: 8px;"
          />

          <!-- File Upload -->
          <input 
            type="file" 
            multiple 
            accept=".pdf,.docx" 
            @change="handleFileUpload"
            :disabled="files.length >= 100"
            style="margin-top: 10px; margin-bottom: 10px; width: 100%; padding: 8px;"
          />

          <div v-if="files.length > 0" style="margin-top: 10px;">
            <p v-for="(file, index) in files" :key="index">{{ file.name }}</p>
          </div>

          <!-- Upload button -->
          <button @click="uploadResumes" :disabled="files.length === 0" style="margin-top: 20px; padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px;">
            Upload Resumes
          </button>
        </div>
      </div>


      <div class="table-responsive whitespace-nowrap rounded-primary">
        <table class="table">
          <thead>
            <tr>
              <th>{{ trans('Candidate') }}</th>
              <th>{{ trans('Service') }}</th>
              <th>{{ trans('Is Starred') }}</th>
              <th>{{ trans('Status') }}</th>
              <th class="text-left">{{ trans('Created At') }}</th>
              <th class="flex justify-end">{{ trans('Action') }}</th>
            </tr>
          </thead>
          <tbody class="list" v-if="localCandidates && localCandidates.total != 0">
            <tr v-for="candidate in localCandidates.data" :key="candidate.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar avatar-circle">
                    <img class="avatar-img" v-lazy="candidate?.avatar == null
                        ? `https://ui-avatars.com/api/?name=${candidate.name}` 
                        : `${candidate?.avatar}`"
                    />
                  </div>
                  <div>
                    <h6 class="whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-100">
                      <Link :href="route('admin.candidates.show', candidate)" class="text-dark">
                        {{ candidate.name }}
                      </Link>
                    </h6>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                      {{ candidate.email }}
                    </p>
                  </div>
                </div>
              </td>

              <td>{{ candidate.service?.title }}</td>

              <td>
                <span v-if="candidate.is_star" class="badge badge-primary">{{ trans('Starred') }}</span>
                <span v-else class="badge badge-danger">{{ trans('Not Starred') }}</span>
              </td>

              <td>
                <span :class="candidate.status == 1 ? 'badge badge-success' : 'badge badge-danger'">
                  {{ candidate.status == 1 ? 'Active' : 'Suspended' }}
                </span>
              </td>

              <td class="text-center">
                {{ candidate.created_at_date }}
              </td>
              <td>
                <div class="flex justify-end">
                  <div class="dropdown" data-placement="bottom-start">
                    <div class="dropdown-toggle">
                      <i class="w-6 text-slate-400" data-feather="more-horizontal"></i>
                    </div>
                    <div class="dropdown-content w-40">
                      <ul class="dropdown-list">
                        <li class="dropdown-list-item">
                          <Link :href="route('admin.candidates.show', candidate.id)" class="dropdown-link">
                            <i class="h-5 text-slate-400" data-feather="external-link"></i>
                            <span>{{ trans('View') }}</span>
                          </Link>
                        </li>

                        <li class="dropdown-list-item">
                          <a target="_blank" :href="route('candidates.show', candidate.username)" class="dropdown-link">
                            <i class="h-5 text-slate-400" data-feather="external-link"></i>
                            <span>{{ trans('View Profile') }}</span>
                          </a>
                        </li>

                        <li class="dropdown-list-item">
                          <Link :href="route('admin.candidates.edit', candidate)" class="dropdown-link">
                            <i class="h-5 text-slate-400" data-feather="edit"></i>
                            <span>{{ trans('Edit') }}</span>
                          </Link>
                        </li>

                        <li class="dropdown-list-item">
                          <button @click="deleteRow(route('admin.candidates.destroy', candidate))" class="dropdown-link">
                            <i class="h-5 text-slate-400" data-feather="trash"></i>
                            {{ trans('Delete') }}
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
          <NoDataFound v-else for-table="true" />
        </table>
        <Paginate :links="localCandidates.links" />
      </div>
    </div>
  </main>
</template>


