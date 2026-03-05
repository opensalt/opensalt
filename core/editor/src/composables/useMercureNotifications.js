import { ref, watch, onUnmounted } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useDocumentStore } from '../stores/documentStore';
import { logger } from '../utils/logger';
import { frameworkCacheService } from '../services/frameworkCacheService';

export function useMercureNotifications() {
    const currentDocumentStore = useCurrentDocumentStore();
    const documentStore = useDocumentStore();
    let eventSource = null;
    const currentTopic = ref(null);
    const subscribedDocIds = ref([]);

    async function connect(documentId) {
        disconnect();
        if (!documentId) return;

        if (typeof EventSource === 'undefined') {
            logger.warn('EventSource is not supported in this browser.');
            return;
        }

        // Gather documentId and any related documents
        const docIds = new Set([documentId]);

        try {
            // First try to look up from the local cache to avoid another network request
            const cachedDocs = await frameworkCacheService.getRelatedFrameworks(documentId);
            if (Array.isArray(cachedDocs)) {
                cachedDocs.forEach((doc) => {
                    if (doc.identifier) docIds.add(doc.identifier);
                });
            }
        } catch (err) {
            logger.warn('Failed to fetch related documents for Mercure subscription:', err);
        }

        subscribedDocIds.value = Array.from(docIds);
        currentTopic.value = subscribedDocIds.value.map(id => `doc-updates/${id}`).join(', ');

        const url = new URL(window.location.protocol + '//' + window.location.host + '/.well-known/mercure');

        // Append topic for each document we want to listen to
        subscribedDocIds.value.forEach(id => {
            url.searchParams.append('topic', `doc-updates/${id}`);
        });

        logger.debug(`Connecting to Mercure for topics: ${currentTopic.value}`);
        eventSource = new EventSource(url);

        eventSource.onmessage = async (event) => {
            try {
                const data = JSON.parse(event.data);
                await handleNotification(data);
            } catch (e) {
                logger.error('Failed to parse Mercure notification:', e);
            }
        };

        eventSource.onerror = (error) => {
            logger.error('Mercure connection error:', error);
        };
    }

    function disconnect() {
        if (eventSource) {
            logger.debug(`Disconnecting from Mercure topics: ${currentTopic.value}`);
            eventSource.close();
            eventSource = null;
            currentTopic.value = null;
            subscribedDocIds.value = [];
        }
    }

    async function handleNotification(notification) {
        // Basic validation that we received some changes payload
        if (notification && notification.changes) {
            logger.debug('Received Mercure notification of changes:', notification.changes);

            // Revalidate all subscribed documents to ensure data is fresh
            subscribedDocIds.value.forEach(async (id) => {
                try {
                    await documentStore.revalidatePackage(id);
                } catch (e) {
                    logger.error(`Failed to revalidate package ${id} after notification:`, e);
                }
            });
        }
    }

    // Ensure cleanup
    onUnmounted(() => {
        disconnect();
    });

    return { connect, disconnect, currentTopic };
}
