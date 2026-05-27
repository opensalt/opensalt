// tests/components/BaseModal.focus.test.js
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import BaseModal from '@/components/shared/BaseModal.vue'

const mountModal = (props = {}) => {
  return mount(BaseModal, {
    props: { isOpen: true, title: 'Test Modal', ...props },
    slots: { default: '<p>Modal content</p><button>Focusable</button>' },
    global: {
      plugins: [createPinia()],
      stubs: { teleport: true }
    },
    attachTo: document.body
  })
}

describe('BaseModal Accessibility', () => {
  beforeEach(() => setActivePinia(createPinia()))

  afterEach(() => {
    document.body.style.overflow = ''
  })

  it('has role="dialog" when open', () => {
    const wrapper = mountModal()
    const dialog = wrapper.find('[role="dialog"]')
    expect(dialog.exists()).toBe(true)
  })

  it('has aria-modal="true" when open', () => {
    const wrapper = mountModal()
    expect(wrapper.find('[aria-modal="true"]').exists()).toBe(true)
  })

  it('does NOT have aria-hidden="true" when open', () => {
    const wrapper = mountModal()
    const dialog = wrapper.find('[role="dialog"]')
    expect(dialog.attributes('aria-hidden')).not.toBe('true')
  })

  it('has aria-hidden="true" when closed', async () => {
    const wrapper = mountModal({ isOpen: false })
    const dialog = wrapper.find('[role="dialog"]')
    expect(dialog.attributes('aria-hidden')).toBe('true')
  })

  it('moves focus to first focusable element on open', async () => {
    const wrapper = mountModal({ isOpen: false })
    await wrapper.setProps({ isOpen: true })
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    // The first focusable element is the .btn-close in the modal header
    const firstButton = wrapper.find('.btn-close')
    expect(document.activeElement).toBe(firstButton.element)
  })

  it('restores focus to trigger on close', async () => {
    const trigger = document.createElement('button')
    trigger.textContent = 'Open'
    document.body.appendChild(trigger)
    trigger.focus()

    const wrapper = mountModal({ isOpen: true })
    await wrapper.setProps({ isOpen: false })
    await wrapper.vm.$nextTick()
    expect(document.activeElement).toBe(trigger)
    document.body.removeChild(trigger)
  })
})
