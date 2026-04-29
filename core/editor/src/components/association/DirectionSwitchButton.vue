<template>
  <div class="col-auto d-flex flex-column align-items-center">
    <!-- Direction button with item names on each side -->
    <button
      type="button"
      class="btn btn-outline-secondary direction-switch-btn"
      :title="title"
      :aria-label="ariaLabel"
      :disabled="disabled"
      @click="$emit('switch')"
    >
      <span class="direction-side direction-left">
        <span class="direction-label">From:</span>
        <span class="direction-item-name">{{ leftSideText }}</span>
      </span>
      <span class="direction-arrow">
        <i
          :class="directionIcon"
          class="bi fs-5"
        />
      </span>
      <span class="direction-side direction-right">
        <span class="direction-label">To:</span>
        <span class="direction-item-name">{{ rightSideText }}</span>
      </span>
    </button>
    <small class="text-muted mt-2">Click to switch direction</small>
  </div>
</template>

<script setup>
/**
 * DirectionSwitchButton Component
 *
 * A button component for switching the direction of an association.
 * Displays the left and right side item names with an arrow between them.
 */

defineProps({
  /**
   * Short text for the left side (From)
   */
  leftSideText: {
    type: String,
    default: ''
  },
  /**
   * Short text for the right side (To)
   */
  rightSideText: {
    type: String,
    default: ''
  },
  /**
   * Icon class for the direction arrow
   */
  directionIcon: {
    type: String,
    default: 'bi-arrow-right'
  },
  /**
   * Title attribute for the button
   */
  title: {
    type: String,
    default: 'Click to reverse direction'
  },
  /**
   * Aria label for accessibility
   */
  ariaLabel: {
    type: String,
    default: 'Switch association direction'
  },
  /**
   * Whether the button is disabled
   */
  disabled: {
    type: Boolean,
    default: false
  }
});

defineEmits(['switch']);
</script>

<style scoped>
/* Direction switch button styling */
.direction-switch-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  transition: all 0.2s ease;
  min-width: 210px;
  justify-content: space-between;
}

.direction-switch-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
  border-color: #0d6efd;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.direction-switch-btn:focus {
  outline: none;
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.direction-switch-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.direction-side {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  flex: 1;
}

.direction-right {
  align-items: flex-end;
  text-align: right;
}

.direction-label {
  font-size: 0.7rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.125rem;
}

.direction-item-name {
  font-size: 0.85rem;
  font-weight: 500;
  color: #212529;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.direction-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d6efd;
  flex-shrink: 0;
}
</style>
