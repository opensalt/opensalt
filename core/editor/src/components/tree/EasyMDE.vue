<template>
  <div
    ref="wrapperRef"
    class="easymde-wrapper"
  >
    <textarea
      :id="id"
      ref="textareaRef"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :name="name"
      :aria-label="label"
      @input="handleInput"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import mde from '../../utils/mde'
import 'easymde/dist/easymde.min.css'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  id: {
    type: String,
    default: ''
  },
  name: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: 'Markdown editor'
  }
})

const emit = defineEmits(['update:modelValue'])

const textareaRef = ref(null)
const wrapperRef = ref(null)
let easyMDEInstance = null

const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
}

const updateValue = async (newValue) => {
  if (easyMDEInstance && newValue !== easyMDEInstance.value()) {
    await nextTick()
    easyMDEInstance.value(newValue)
  }
}

watch(() => props.modelValue, (newValue) => {
  updateValue(newValue)
})

onMounted(async () => {
  if (textareaRef.value) {
    // mde() is async to support lazy loading of the markdown renderer
    easyMDEInstance = await mde(textareaRef.value)

    // Set initial value
    if (props.modelValue) {
      easyMDEInstance.value(props.modelValue)
    }

    // Listen for changes in the editor
    easyMDEInstance.codemirror.on('change', () => {
      const value = easyMDEInstance.value()
      emit('update:modelValue', value)
    })

    // Apply accessibility attributes to the EasyMDE container
    const container = wrapperRef.value?.querySelector('.EasyMDEContainer')
    if (container) {
      container.setAttribute('role', 'textbox')
      container.setAttribute('aria-multiline', 'true')
      container.setAttribute('aria-label', props.label)
    }
  }
})

onUnmounted(() => {
  if (easyMDEInstance) {
    // Clean up the EasyMDE instance
    easyMDEInstance.toTextArea()
    easyMDEInstance = null
  }
})
</script>

<style scoped>
.easymde-wrapper {
  width: 100%;
}

.easymde-wrapper :deep(.CodeMirror) {
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  min-height: 80px;
}

.easymde-wrapper :deep(.CodeMirror-focused) {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.easymde-wrapper :deep(.editor-toolbar) {
  border-bottom: 1px solid #ced4da;
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
}

.easymde-wrapper :deep(.editor-preview) {
  border: 1px solid #ced4da;
  border-top: none;
  border-bottom-left-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
}
</style>
