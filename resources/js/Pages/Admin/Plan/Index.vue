<template>
  <main class="container flex-grow p-4 sm:p-6">
    <HeaderSegment :title="trans('Subscriptions')" :segments="segments" :buttons="buttons" />

    <div class="space-y-6">
      <div v-if="plans.length != 0" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card" v-for="plan in plans" :key="plan.id">
          <div class="flex flex-col justify-between h-full card-body">
            <div>
              <div class="text-center">
                <h5>{{ plan.title }}</h5>
                <h4>{{ plan.price_format }}</h4>
                {{ plan.days == 30 ? trans('Per month') : trans('Per year') }}

                <p class="text-muted">{{ trans('Active Users') }} ({{ plan.activeuser_count }})</p>
              </div>
              <hr class="mt-3 opacity-60" />

              <div class="mt-3 text-left" v-for="(planData, key) in plan.data" :key="planData.key">
                <i
                  v-if="planData.toString == 'true' || planData == 'false'"
                  :class="
                    planData == true
                      ? 'far fa-check-circle text-green-600'
                      : 'fas fa-times-circle text-red-600'
                  "
                ></i>

                <i class="text-green-600 far fa-check-circle" v-else></i>
                {{ key.replace(/_/g, ' ') }}
                {{
                  planData == '-1'
                    ? '(Unlimited)'
                    : planData == 'true' || planData == 'false'
                    ? ''
                    : `(${planData})`
                }}
              </div>
              
              <!-- Resumes -->
              <!-- <div class="mt-3 text-left">
                <i class="text-green-600 far fa-check-circle"></i>
                <span class="ml-1">Resumes 5000</span>
              </div> -->
            </div>

            <div class="mt-4">
              <hr class="opacity-60" />

              <div class="flex justify-center gap-2 mt-4">
                <Link :href="route('admin.plan.edit', plan.id)" class="px-5 btn btn-primary"
                  ><i class="fa fa-edit" aria-hidden="true"></i> Edit
                </Link>

                <button
                  @click="
                    plan.activeuser_count == 0 ? deleteRow('/admin/plan/' + plan.id) : toastError()
                  "
                  class="px-5 text-left btn btn-sm btn-danger delete-confirm"
                  data-icon="fa fa-plus-circle"
                >
                  <i class="fa fa-trash" aria-hidden="true"></i>
                  {{ trans('Delete') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Alert v-else type="info" :text="trans('Opps you have not created any plan....')" />
    </div>
  </main>

  <div id="addNewPlanDrawer" class="drawer drawer-right">
    <div class="drawer-content">
      <form @submit.prevent="storePlan()">
        <div class="card">
          <div class="card-body">
            <!-- Plan Name -->
            <div class="my-2">
              <label>{{ trans('Plan Name') }}</label>
              <input type="text" name="title" required="" class="input" v-model="form.title" />
            </div>

            <!-- Select Duration -->
            <div class="my-2">
              <label>{{ trans('Select Duration') }}</label>
              <select class="select" name="days" v-model="form.days">
                <option value="30">{{ trans('Monthly') }}</option>
                <option value="365">{{ trans('Yearly') }}</option>
              </select>
            </div>

            <!-- Price -->
            <div class="my-2">
              <label>{{ trans('Price') }}</label>
              <input type="number" name="price" v-model="form.price" step="any" required class="input" />
            </div>

            <!-- Job Limit -->
            <div class="mb-2">
              <label>{{ trans('Job Limit') }}</label>
              <input type="number" v-model="form.plan_data['job_limit']" required class="input" />
            </div>

            <!-- Featured Jobs -->
            <div class="mb-2">
              <label>{{ trans('Featured Jobs') }}</label>
              <input type="number" v-model="form.plan_data['featured_jobs']" required class="input" />
            </div>

            <!-- Live For (Days) -->
            <div class="mb-2">
              <label>{{ trans('Live For (Days)') }}</label>
              <input type="number" v-model="form.plan_data['live_job_for_days']" required class="input" />
            </div>

            <!-- AI Credits -->
            <div class="mb-2">
              <label>{{ trans('Ai Credits') }}</label>
              <input type="number" v-model="form.plan_data['ai_credits']" required class="input" />
            </div>

            <!-- Resumes -->
            <div class="mb-2">
              <label>{{ trans('Resumes') }}</label>
              <input type="number" v-model="form.plan_data['resumes']" required class="input" />
            </div>

            <!-- Featured Plan -->
            <div class="mb-2">
              <label for="toggle-featured" class="toggle toggle-sm">
                <input
                  v-model="form.is_featured"
                  class="toggle-input peer sr-only"
                  id="toggle-featured"
                  type="checkbox"
                />
                <div class="toggle-body"></div>
                <span class="label label-md">{{ trans('Featured in home page?') }}</span>
              </label>
            </div>

            <!-- Is Recommended -->
            <div class="mb-2">
              <label for="toggle-is_recommended" class="toggle toggle-sm">
                <input
                  v-model="form.is_recommended"
                  class="toggle-input peer sr-only"
                  id="toggle-is_recommended"
                  type="checkbox"
                />
                <div class="toggle-body"></div>
                <span class="label label-md">{{ trans('Is recommended?') }}</span>
              </label>
            </div>

            <!-- Accept Trial -->
            <div class="mb-2">
              <label for="toggle-is_trial" class="toggle toggle-sm">
                <input
                  v-model="form.is_trial"
                  class="toggle-input peer sr-only"
                  id="toggle-is_trial"
                  type="checkbox"
                />
                <div class="toggle-body"></div>
                <span class="label label-md">{{ trans('Accept Trial?') }}</span>
              </label>
            </div>

            <!-- Trial Days -->
            <div v-if="form.is_trial" class="from-group trial-days mb-2 mt-2">
              <label class="col-lg-12">{{ trans('Trial days') }}</label>
              <div class="col-lg-12">
                <input type="number" v-model="form.trial_days" name="trial_days" class="input" required />
              </div>
            </div>

            <!-- Activate This Plan -->
            <div class="mb-2">
              <label for="toggle-status" class="toggle toggle-sm">
                <input
                  v-model="form.status"
                  class="toggle-input peer sr-only"
                  id="toggle-status"
                  type="checkbox"
                />
                <div class="toggle-body"></div>
                <span class="label label-md">{{ trans('Activate This Plan?') }}</span>
              </label>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
              <SpinnerBtn classes="btn btn-primary" :processing="form.processing" btn-text="Add Plan" />
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import AdminLayout from '@/Layouts/Admin.vue'
import Alert from '@/Components/Admin/Alert.vue'
import SpinnerBtn from '@/Components/Admin/SpinnerBtn.vue'
import { onMounted } from 'vue'
import HeaderSegment from '@/Layouts/Admin/HeaderSegment.vue'
import { useForm } from '@inertiajs/vue3'
import notify from '@/Plugins/Admin/notify'
import drawer from '@/Plugins/Admin/drawer'

import sharedComposable from '@/Composables/sharedComposable'

defineOptions({ layout: AdminLayout })
onMounted(() => {
  drawer.init()
})

const props = defineProps(['segments', 'buttons', 'plans'])
const { deleteRow } = sharedComposable()
const form = useForm({
  title: null,
  days: 30,
  price: null,
  plan_data: {
    job_limit: 0,
    featured_jobs: 0,
    live_job_for_days: 0,
    ai_credits: 0,
    resumes: 0
  },
  is_featured: false,
  is_recommended: false,
  is_trial: false,
  status: true,
  trial_days: 0
})
function toastError() {
  notify.danger('You cant delete this plan because someone already using this plan')
}
const storePlan = () => {
  form.post('/admin/plan', {
    onSuccess: () => {
      form.reset()
      notify.success('Plan has been added successfully')
      drawer.of('#addNewPlanDrawer').hide()
    }
  })
}
</script>
