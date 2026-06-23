<template>
  <span
    ref="triggerRef"
    class="item-statement-popover-trigger"
    tabindex="0"
    @mouseenter="onEnter"
    @mouseleave="onLeave"
    @focus="onEnter"
    @blur="onLeave"
  >
    <slot />
    <Teleport to="body">
      <div
        v-if="popoverVisible"
        ref="popoverRef"
        class="statement-popover"
        role="tooltip"
        :style="popoverStyle"
        v-html="statementHtml"
      />
    </Teleport>
  </span>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useStatementPopover } from '@/composables/useStatementPopover';

const props = defineProps({
  statement: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const triggerRef = ref(null);
const popoverRef = ref(null);
const popoverStyle = ref({});

const { showPopover, statementHtml, onTriggerEnter, onTriggerLeave } =
  useStatementPopover(() => props.statement);

const popoverVisible = computed(
  () => showPopover.value && !props.disabled && statementHtml.value !== ''
);

function onEnter() {
  if (props.disabled || !props.statement) return;
  onTriggerEnter();
}

function onLeave() {
  onTriggerLeave();
}

async function updatePosition() {
  await nextTick();
  const el = triggerRef.value;
  if (!el) return;
  const rect = el.getBoundingClientRect();
  const popoverEl = popoverRef.value;
  const popoverWidth = popoverEl?.offsetWidth || 400;
  const popoverHeight = popoverEl?.offsetHeight || 100;

  let top = rect.bottom + 5;
  let left = rect.left;
  const vw = window.innerWidth;
  const vh = window.innerHeight;

  if (left + popoverWidth > vw) {
    left = Math.max(8, vw - popoverWidth - 8);
  }
  if (top + popoverHeight > vh) {
    top = Math.max(0, rect.top - popoverHeight - 5);
  }

  popoverStyle.value = { top: `${top}px`, left: `${left}px` };
}

watch(popoverVisible, (visible) => {
  if (visible) updatePosition();
});

function onScrollOrResize() {
  if (popoverVisible.value) updatePosition();
}

onMounted(() => {
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize, true);
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize, true);
});
</script>

<style scoped>
.item-statement-popover-trigger {
  position: relative;
  display: inline-block;
}

.statement-popover {
  position: fixed;
  z-index: 9999;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
  max-width: 400px;
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: pre-wrap;
  word-wrap: break-word;
  pointer-events: none;
}

.statement-popover :deep(:last-child) {
  margin-bottom: 0;
}

.statement-popover::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 12px;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #ddd;
  pointer-events: none;
}

.statement-popover::after {
  content: '';
  position: absolute;
  top: -5px;
  left: 13px;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-bottom: 5px solid white;
  pointer-events: none;
}
</style>
