import { vi } from 'vitest';
import { config } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';

vi.stubGlobal('localStorage', {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
});

vi.stubGlobal('fetch', vi.fn());

config.global.plugins = [];

beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});
