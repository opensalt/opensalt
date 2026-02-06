import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSessionStore = defineStore('session', () => {
    const isAuthenticated = ref(false);
    const sessionStatus = ref('active'); // active, warning, expired
    const warningLevel = ref(null); // info, warning, warning-2, expired
    const remainingTime = ref(0);
    const checkInterval = ref(null);
    const sessionModalVisible = ref(false);

    // Initialize session check
    function init() {
        // Check session immediately to determine auth state
        checkSession();
    }

    async function checkSession() {
        try {
            // Use raw fetch to handle session state without global interceptors interference
            const response = await fetch('/session/check', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                // 401, 403, or other error means no active session
                handleExpired();
                return;
            }

            const json = await response.json();
            const time = json.remainingTime;
            remainingTime.value = time;

            if (time > 0) {
                // Valid session exists
                if (!isAuthenticated.value) {
                    isAuthenticated.value = true;
                }
                handleRemainingTime(time);
            } else {
                // Session expired or invalid according to server
                handleExpired();
            }

        } catch (error) {
            console.warn('Session check failed', error);
            handleExpired();
        }
    }

    function handleExpired() {
        // If we believed we were authenticated, this is a transition to expired -> show warning
        if (isAuthenticated.value) {
            setWarning('expired');
        } else {
            // If we were never authenticated (or already handled expiration), just ensure state is correct
            // Do NOT show a modal for initial page load of public/guest user
            isAuthenticated.value = false;
            sessionStatus.value = 'expired';
            // ensure modal is closed if it wasn't already
            sessionModalVisible.value = false;
        }

        if (checkInterval.value) clearTimeout(checkInterval.value);
    }

    function handleRemainingTime(time) {
        // Schedule next check based on time remaining logic
        let nextCheckDelay = 0;

        if (time < 11) {
            setWarning('warning-2');
            nextCheckDelay = (time * 1000) + 100;
        } else if (time < 61) {
            setWarning('warning');
            nextCheckDelay = (time - 10) * 1000 + 100;
        } else if (time < 301) {
            setWarning('info');
            nextCheckDelay = (time - 60) * 1000 + 100;
        } else {
            clearWarning();
            // "Set timeout for remainingTime - 300 to re-check"
            nextCheckDelay = (time - 300) * 1000 + 100;
        }

        // Ensure delay is at least reasonable (e.g. 1s) to avoid rapid polling loops if calculation is off
        if (nextCheckDelay < 1000) nextCheckDelay = 1000;

        if (checkInterval.value) clearTimeout(checkInterval.value);
        checkInterval.value = setTimeout(() => {
            checkSession();
        }, nextCheckDelay);
    }

    function setWarning(level) {
        warningLevel.value = level;
        if (level === 'expired') {
            sessionStatus.value = 'expired';
            isAuthenticated.value = false;
        } else {
            sessionStatus.value = 'warning';
        }
        sessionModalVisible.value = true;
    }

    function clearWarning() {
        warningLevel.value = null;
        sessionStatus.value = 'active';
        sessionModalVisible.value = false;
    }

    async function renewSession() {
        try {
            await fetch('/session/renew');
            checkSession();
        } catch (error) {
            console.error('Failed to renew session', error);
        }
    }

    return {
        isAuthenticated,
        sessionStatus,
        warningLevel,
        remainingTime,
        sessionModalVisible,
        init,
        checkSession,
        renewSession,
        clearWarning
    };
});
