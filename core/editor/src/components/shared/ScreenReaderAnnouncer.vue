<template>
  <!-- Screen reader announcer container - visually hidden but accessible to screen readers -->
  <!-- Note: aria-hidden is NOT used here because aria-live regions must be perceivable by screen readers -->
  <div id="a11y-announcer-container" class="sr-only">
    <!-- Polite region for general announcements (non-interruptive) -->
    <div
      id="a11y-announcer-polite"
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      {{ politeMessage }}
    </div>

    <!-- Assertive region for important announcements (immediate) -->
    <div
      id="a11y-announcer-assertive"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      {{ assertiveMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  politeMessage: {
    type: String,
    default: ''
  },
  assertiveMessage: {
    type: String,
    default: ''
  }
});

// Watch for message changes and update DOM
// The actual announcement is handled by the useAnnouncer composable
// This component provides the ARIA live regions
watch(() => props.politeMessage, (newMessage) => {
  const politeRegion = document.getElementById('a11y-announcer-polite');
  if (politeRegion) {
    politeRegion.textContent = newMessage;
  }
});

watch(() => props.assertiveMessage, (newMessage) => {
  const assertiveRegion = document.getElementById('a11y-announcer-assertive');
  if (assertiveRegion) {
    assertiveRegion.textContent = newMessage;
  }
});
</script>

<style scoped>
/* Screen reader only class - visually hidden but accessible to screen readers */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Ensure the announcer is never visible */
#a11y-announcer-container {
  pointer-events: none;
  user-select: none;
}
</style>
