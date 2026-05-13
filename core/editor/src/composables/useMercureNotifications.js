import { ref, onUnmounted } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useDocumentStore } from '../stores/documentStore';
import { useEditorContextStore } from '../stores/editorContextStore';
import { logger } from '../utils/logger';
import { api } from '../services/api.js';

const relatedDocCache = new Map();

export function useMercureNotifications() {
    const currentDocumentStore = useCurrentDocumentStore();
    const documentStore = useDocumentStore();
    const editorContextStore = useEditorContextStore();
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

        const docIds = new Set([documentId]);

        try {
            let relatedDocs = relatedDocCache.get(documentId);
            if (!relatedDocs) {
                relatedDocs = await api.getRelatedDocuments(documentId);
                if (Array.isArray(relatedDocs)) {
                    relatedDocCache.set(documentId, relatedDocs);
                }
            }
            if (Array.isArray(relatedDocs)) {
                relatedDocs.forEach((doc) => {
                    if (doc.identifier) docIds.add(doc.identifier);
                });
            }
        } catch (err) {
            logger.warn('Failed to fetch related documents for Mercure subscription:', err);
        }

        subscribedDocIds.value = Array.from(docIds);
        currentTopic.value = subscribedDocIds.value.map(id => `doc-updates/${id}`).join(', ');

        const url = new URL(window.location.protocol + '//' + window.location.host + '/.well-known/mercure');

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
        if (notification && notification.changes) {
            logger.debug('Received Mercure notification of changes:', notification.changes);

            const activeDocId = editorContextStore.activeWriteDocumentId;

            for (const id of subscribedDocIds.value) {
                try {
                    for (const [itemIdentifier, details] of editorContextStore.itemDetailsCache) {
                        if (details.documentIdentifier === id) {
                            editorContextStore.invalidateItemDetails(itemIdentifier);
                        }
                    }
                    currentDocumentStore.invalidateItemDetailsCache();

                    documentStore.invalidateTreeCache(id);

                    if (id === activeDocId) {
                        await documentStore.fetchTree(id);
                    }
                } catch (e) {
                    logger.error(`Failed to invalidate caches for document ${id} after notification:`, e);
                }
            }
        }
    }

    onUnmounted(() => {
        disconnect();
    });

    return { connect, disconnect, currentTopic };
}
