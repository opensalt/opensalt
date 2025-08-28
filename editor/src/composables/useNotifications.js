import { inject } from 'vue';

export function useNotifications() {
  const notify = inject('notify');
  if (!notify) {
    throw new Error('Notification provider not found');
  }
  return { notify };
} 