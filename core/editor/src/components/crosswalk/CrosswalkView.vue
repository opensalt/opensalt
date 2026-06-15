<template>
  <div class="crosswalk-view h-100 d-flex flex-column">
    <ul
      class="nav nav-tabs mb-3"
      role="tablist"
    >
      <li
        class="nav-item"
        role="presentation"
      >
        <button
          class="nav-link"
          :class="{ active: activeTab === 'create' }"
          role="tab"
          :aria-selected="activeTab === 'create'"
          @click="switchTab('create')"
        >
          <i
            class="bi bi-magic me-1"
            aria-hidden="true"
          />
          Create Crosswalk
        </button>
      </li>
      <li
        class="nav-item"
        role="presentation"
      >
        <button
          class="nav-link"
          :class="{ active: activeTab === 'review' }"
          role="tab"
          :aria-selected="activeTab === 'review'"
          @click="switchTab('review')"
        >
          <i
            class="bi bi-check2-square me-1"
            aria-hidden="true"
          />
          Review Crosswalk
        </button>
      </li>
    </ul>

    <div
      class="tab-content flex-grow-1 overflow-hidden d-flex flex-column"
      style="min-height: 0;"
    >
      <CreateTab
        v-if="activeTab === 'create'"
        role="tabpanel"
        aria-labelledby="create-tab"
      />
      <ReviewTab
        v-else
        role="tabpanel"
        aria-labelledby="review-tab"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CreateTab from './CreateTab.vue';
import ReviewTab from './ReviewTab.vue';

const route = useRoute();
const router = useRouter();
const activeTab = ref('create');

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'review') {
    activeTab.value = 'review';
  } else if (newTab === 'create' || !newTab) {
    activeTab.value = 'create';
  }
}, { immediate: true });

function switchTab(tab) {
  activeTab.value = tab;
  router.replace({ query: { ...route.query, tab } });
}
</script>
