<template>
  <div v-if="candidate" class="candidate-profile-card list-layout mb-25" :class="{ favourite: candidate?.is_star }">
    <div class="d-flex">
      <div class="cadidate-avatar online position-relative d-block m-auto">
        <Link :href="`/candidates/${candidate.username}`" class="rounded-circle">
          <img v-lazy="candidate?.avatar == null
            ? `https://ui-avatars.com/api/?name=${candidate?.name}`
            : `${candidate?.avatar}`" alt="avatar" class="lazy-img rounded-circle" />
        </Link>
      </div>
      <div class="right-side">
        <div class="row gx-1 align-items-center">
          <div class="col-xl-3">
            <div class="position-relative">
              <h4 class="candidate-name mb-0">
                <Link :href="`/candidates/${candidate.username}`" class="tran3s">
                  {{ candidate.name }}
                </Link>
              </h4>
              <div class="candidate-post">{{ candidate.designation }}</div>
              <ul class="cadidate-skills style-none d-flex align-items-center text-nowrap">
                <li v-for="(item, index) in tags(candidate.tags)?.tagItems ?? []" :key="index">
                  {{ item.title }}
                </li>
                <li class="more" v-if="tags(candidate.tags)?.showMoreTags">
                  {{ tags(candidate.tags)?.remainingTagsCount }}+
                </li>
              </ul>
            </div>
          </div>
          <div class="col-xl-3 col-md-4 col-sm-6 mb-2">
            <div class="candidate-info">
              <span>Salary</span>
              <div v-if="candidate.meta?.expected_salary && candidate.meta?.expected_salary > 1">
                {{ formatNumber(candidate.meta?.expected_salary) }}/ {{ candidate.meta?.currency }}
              </div>
              <div v-else>Negotiable</div>
            </div>
          </div>
          <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="candidate-info">
              <span>Location</span>
              <div class="text-truncate" :title="candidate.location?.join(', ')">
                {{ candidate.location?.join(', ') }}
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-4">
            <div class="d-flex justify-content-lg-end">
              <button @click="store.toggleBookmark(candidate.id, candidate.isBookmarked)"
                class="save-btn rounded-circle tran3s mt-10 text-center">
                <i class="bi" :class="[candidate.isBookmarked ? 'text-danger bi-heart-fill' : 'bi-heart']"></i>
              </button>

              <Link :href="`/candidates/${candidate.username}`" class="profile-btn tran3s ms-md-2 sm-mt-20 mt-10">
                View Profile
              </Link>

              <!-- Conditionally render the download button only if the resume exists and if not viewed -->
              <div v-if="candidate.resume && !resumeViewed" class="ms-md-2 sm-mt-20 mt-20">
                <a @click="downloadResume(candidate)" class="resume-btn tran3s">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </a>
              </div>

              <!-- Show "Already Viewed" text if the resume is downloaded -->
              <div class="ms-md-2 sm-mt-20 mt-20" v-if="resumeViewed">
                <a @click="downloadResume(candidate)"><span>Viewed</span></a>
              </div>

              <!-- Show a message or alternative element if the user has reached the download limit -->
              <div class="ms-md-2 sm-mt-20 mt-20 limit-reached-msg" v-if="hasReachedLimit">
                You have reached the download limit.
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else>
    Loading candidate...
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { defineProps } from 'vue';
import sharedComposable from '@/Composables/sharedComposable';
import { useCandidateFilterStore } from '@/Store/candidateFilterStore';
import axios from 'axios';

// Define props for the candidate data
defineProps({
  candidate: {
    type: Object,
    default: () => ({ is_star: false, name: '', avatar: null, resume: null }) // Default candidate object
  }
});

const { formatNumber } = sharedComposable();
const store = useCandidateFilterStore();

const hasReachedLimit = ref(false); // Track if the user has reached the resume download limit
const resumeViewed = ref(false); // Track if the resume has been viewed/downloaded

// Function to handle tag display with max tag limit
const tags = (tags) => {
  const maxTag = 2;
  const tagItems = tags?.slice(0, maxTag);
  const remainingTagsCount = Math.max(tags?.length - maxTag, 0);
  const showMoreTags = remainingTagsCount > 0;

  return {
    tagItems,
    remainingTagsCount,
    showMoreTags
  };
};

const downloadResume = async (candidate) => {
  try {
    console.log("Attempting to download resume for:", candidate);

    // Check if the resume exists and is not null
    if (!candidate.resume) {
      console.error("Resume not available for:", candidate.name);
      alert('Resume not available for this candidate.');
      return;
    }

    // Send a request to check if the user can download the resume
    const response = await axios.get(`candidates/download-resume/${candidate.id}`);

    // Check if the user has reached the download limit
    if (response.data.limitReached) {
      hasReachedLimit.value = true;
      alert('You have reached the download limit.');
      return;
    }

    // If the response indicates a successful download
    if (response.data.downloaded) {
      const resumeUrl = `${window.location.origin}/${candidate.resume}`; // Build the full URL to the resume

      // Trigger download using the URL
      const a = document.createElement('a');
      a.href = resumeUrl; // Use the resume URL

      // Determine the file extension for download naming
      const fileExtension = resumeUrl.endsWith('.docx') ? 'docx' : 'pdf';
      a.setAttribute('download', `${candidate.name}_resume.${fileExtension}`); // Set the name of the downloaded file
      document.body.appendChild(a);
      a.click();
      a.remove();
      console.log("Download triggered for:", candidate.name);
      resumeViewed.value = true; // Mark the resume as viewed
    } else {
      console.log("User has already downloaded this resume.");
      alert('You have already downloaded this resume.');
      resumeViewed.value = true; // Mark as viewed since it was already downloaded
    }
  } catch (error) {
    console.error('Error downloading resume:', error.response ? error.response.data : error.message);
    alert('You have reached the limit of download Resume.');
  }
};

</script>

<style scoped>
.limit-reached-msg {
  font-size: 14px;
  font-weight: bold;
  color: red;
}
</style>
