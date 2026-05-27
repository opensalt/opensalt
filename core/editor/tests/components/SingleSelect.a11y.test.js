import { mount } from '@vue/test-utils'
import SingleSelect from '@/components/tree/SingleSelect.vue'

describe('SingleSelect Accessibility', () => {
  const options = [
    { value: 'a', label: 'Option A' },
    { value: 'b', label: 'Option B' },
    { value: 'c', label: 'Option C' },
  ]

  const mountSelect = (props = {}) =>
    mount(SingleSelect, {
      props: { options, modelValue: '', ...props },
      global: { stubs: { teleport: true } }
    })

  it('has role="combobox" on trigger', () => {
    const wrapper = mountSelect()
    expect(wrapper.find('[role="combobox"]').exists()).toBe(true)
  })

  it('has aria-expanded on trigger', () => {
    const wrapper = mountSelect()
    expect(wrapper.find('[aria-expanded]').exists()).toBe(true)
  })

  it('has role="listbox" on dropdown', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('[role="listbox"]').exists()).toBe(true)
  })

  it('has role="option" on items', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('[role="option"]').exists()).toBe(true)
  })

  it('opens dropdown on Enter key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(true)
  })

  it('opens dropdown on Space key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: ' ' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(true)
  })

  it('closes dropdown on Escape key', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: 'Enter' })
    await trigger.trigger('keydown', { key: 'Escape' })
    expect(wrapper.find('[role="listbox"]').isVisible()).toBe(false)
  })

  it('navigates options with arrow keys', async () => {
    const wrapper = mountSelect()
    const trigger = wrapper.find('[role="combobox"]')
    await trigger.trigger('keydown', { key: 'Enter' })
    await trigger.trigger('keydown', { key: 'ArrowDown' })
    const combo = wrapper.find('[role="combobox"]')
    expect(combo.attributes('aria-activedescendant')).toBeTruthy()
  })
})
