import { mount } from '@vue/test-utils'
import { defineComponent, h, nextTick, ref } from 'vue'
import MultiSelect from '@/components/tree/MultiSelect.vue'

const options = [
  { code: '01', label: '01 - First grade' },
  { code: '05', label: '05 - Fifth grade' },
  { code: '07', label: '07 - Seventh grade' },
]

// Harness with a real v-model, like ChildModal uses. Attached to the document so
// document-level listeners and bubbling clicks behave like a real page.
function mountHarness(modelValue = []) {
  const state = ref(modelValue)
  const Parent = defineComponent({
    setup() {
      return () => h('div', [
        h(MultiSelect, {
          id: 'test-multiselect',
          options,
          optionValue: 'code',
          optionLabel: 'label',
          modelValue: state.value,
          'onUpdate:modelValue': (v) => { state.value = v },
        }),
      ])
    },
  })
  const wrapper = mount(Parent, { attachTo: document.body })
  return { wrapper, state }
}

// Two instances in one app: used for id uniqueness and cross-instance close behaviour
function mountPair() {
  const stateA = ref([])
  const stateB = ref([])
  const Parent = defineComponent({
    setup() {
      return () => h('div', [
        h(MultiSelect, {
          id: 'sel-a',
          options,
          modelValue: stateA.value,
          'onUpdate:modelValue': (v) => { stateA.value = v },
        }),
        h(MultiSelect, {
          id: 'sel-b',
          options,
          modelValue: stateB.value,
          'onUpdate:modelValue': (v) => { stateB.value = v },
        }),
      ])
    },
  })
  const wrapper = mount(Parent, { attachTo: document.body })
  return { wrapper, stateA, stateB }
}

const openDropdown = async (wrapper) => {
  await wrapper.find('.multiselect__tags').trigger('click')
  await nextTick()
}

const isOpen = (wrapper) => wrapper.find('.multiselect__content').isVisible()

describe('MultiSelect', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  describe('mouse selection', () => {
    it('selects once when the option checkbox is clicked', async () => {
      const { wrapper, state } = mountHarness()
      await openDropdown(wrapper)

      const li = wrapper.findAll('.multiselect__option')[0]
      const checkbox = li.find('input[type=checkbox]')
      // A real browser click on the checkbox fires click (bubbling to the row),
      // lets Vue flush, then fires the native change event.
      await checkbox.trigger('click')
      await nextTick()
      checkbox.element.dispatchEvent(new Event('change'))
      await nextTick()

      expect(state.value).toEqual(['01'])
      expect(checkbox.element.checked).toBe(true)
      wrapper.unmount()
    })

    it('deselects once when the checkbox of a selected option is clicked', async () => {
      const { wrapper, state } = mountHarness(['01'])
      await openDropdown(wrapper)

      const li = wrapper.findAll('.multiselect__option')[0]
      const checkbox = li.find('input[type=checkbox]')
      await checkbox.trigger('click')
      await nextTick()
      checkbox.element.dispatchEvent(new Event('change'))
      await nextTick()

      expect(state.value).toEqual([])
      expect(checkbox.element.checked).toBe(false)
      wrapper.unmount()
    })

    it('selects once when the option text is clicked', async () => {
      const { wrapper, state } = mountHarness()
      await openDropdown(wrapper)

      await wrapper.findAll('.multiselect__option-text')[1].trigger('click')
      await nextTick()

      expect(state.value).toEqual(['05'])
      wrapper.unmount()
    })
  })

  describe('ids', () => {
    it('gives each instance unique listbox ids', () => {
      const { wrapper } = mountPair()
      const roots = wrapper.findAll('.multiselect')
      const idA = roots[0].find('[role=listbox]').attributes('id')
      const idB = roots[1].find('[role=listbox]').attributes('id')

      expect(idA).toBeTruthy()
      expect(idB).toBeTruthy()
      expect(idA).not.toBe(idB)
      wrapper.unmount()
    })
  })

  describe('open/close', () => {
    it('closes when a click lands inside a different multiselect', async () => {
      const { wrapper } = mountPair()
      const roots = wrapper.findAll('.multiselect')
      await roots[0].find('.multiselect__tags').trigger('click')
      await nextTick()
      expect(isOpen(roots[0])).toBe(true)

      // Real bubbling click on the other multiselect's trigger
      roots[1].find('.multiselect__tags').element
        .dispatchEvent(new MouseEvent('click', { bubbles: true }))
      await nextTick()

      expect(isOpen(roots[0])).toBe(false)
      wrapper.unmount()
    })

    it('stays open when clicking inside its own dropdown', async () => {
      const { wrapper } = mountHarness()
      await openDropdown(wrapper)

      await wrapper.find('.multiselect__search input').trigger('click')
      await nextTick()
      expect(isOpen(wrapper)).toBe(true)
      wrapper.unmount()
    })
  })

  describe('option labels', () => {
    it('falls back to the option value when the label is an empty string', async () => {
      const Parent = defineComponent({
        setup() {
          const inner = ref([])
          return () => h(MultiSelect, {
            options: [{ id: 98, text: '' }],
            optionValue: 'id',
            optionLabel: 'text',
            modelValue: inner.value,
            'onUpdate:modelValue': (v) => { inner.value = v },
          })
        },
      })
      const wrapper = mount(Parent, { attachTo: document.body })
      await openDropdown(wrapper)

      const optionText = wrapper.find('.multiselect__option-text').text()
      expect(optionText).toBe('98')
      expect(optionText).not.toContain('[object Object]')

      // Selecting it stores the id and shows it as the chip label
      await wrapper.find('.multiselect__option').trigger('click')
      await nextTick()
      expect(wrapper.find('.multiselect__selected-value').text()).toBe('98')
      wrapper.unmount()
    })
  })
})
