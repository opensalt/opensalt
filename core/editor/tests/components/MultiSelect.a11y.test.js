import { mount } from '@vue/test-utils'
import MultiSelect from '@/components/tree/MultiSelect.vue'

describe('MultiSelect Accessibility', () => {
  const options = [
    { value: 'a', label: 'Option A' },
    { value: 'b', label: 'Option B' },
    { value: 'c', label: 'Option C' },
  ]

  const mountSelect = (props = {}) =>
    mount(MultiSelect, {
      props: { options, modelValue: [], ...props },
      global: { stubs: { teleport: true } }
    })

  it('has role="combobox" or aria-haspopup="listbox" on trigger', () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-haspopup="listbox"], [role="combobox"]')
    expect(trigger.exists()).toBe(true)
  })

  it('has aria-expanded on trigger', () => {
    const wrapper = mountSelect()
    expect(wrapper.find('[aria-expanded]').exists()).toBe(true)
  })

  it('has role="listbox" with aria-multiselectable on dropdown', async () => {
    const wrapper = mountSelect()
    // Open the dropdown first
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    const listbox = wrapper.find('[role="listbox"]')
    expect(listbox.exists()).toBe(true)
    expect(listbox.attributes('aria-multiselectable')).toBe('true')
  })

  it('has role="option" on items', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('[role="option"]').exists()).toBe(true)
  })

  it('opens dropdown on Enter key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(true)
  })

  it('opens dropdown on Space key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: ' ' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(true)
  })

  it('closes dropdown on Escape key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    await trigger.trigger('keydown', { key: 'Escape' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(false)
  })

  it('navigates options with arrow keys', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    await trigger.trigger('keydown', { key: 'ArrowDown' })
    // Verify some form of active descendant tracking or focus change
    const triggerEl = wrapper.find('[aria-expanded]')
    expect(triggerEl.attributes('aria-activedescendant')).toBeTruthy()
  })

  it('toggles selection with Space key on highlighted option', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[aria-expanded]')
    await trigger.trigger('keydown', { key: 'Enter' })
    await trigger.trigger('keydown', { key: 'ArrowDown' })
    await trigger.trigger('keydown', { key: ' ' })
    // Should have emitted update:modelValue with the first option selected
    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted[emitted.length - 1][0]).toContain('a')
  })
})
