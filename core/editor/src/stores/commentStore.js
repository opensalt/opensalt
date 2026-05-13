import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

/**
 * Comment Store
 * Manages comments for items and documents
 */
export const useCommentStore = defineStore('comments', () => {
    // State
    const comments = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const currentItem = ref(null); // { identifier, type: 'item' | 'document' }

    // Computed
    const hasComments = computed(() => comments.value.length > 0);

    const topLevelComments = computed(() => {
        return comments.value.filter(c => !c.parent).sort((a, b) =>
            new Date(b.created) - new Date(a.created)
        );
    });

    /**
     * Get replies for a specific comment
     * @param {number} parentId - Parent comment ID
     * @returns {Array} - Array of reply comments
     */
    function getReplies(parentId) {
        return comments.value
            .filter(c => c.parent === parentId)
            .sort((a, b) => new Date(a.created) - new Date(b.created));
    }

    /**
     * Build a hierarchical comment tree
     * @param {number|null} parentId - Parent comment ID
     * @returns {Array} - Nested comment structure
     */
    function buildCommentTree(parentId = null) {
        const topLevel = comments.value
            .filter(c => c.parent === parentId)
            .sort((a, b) => new Date(b.created) - new Date(a.created));

        return topLevel.map(comment => ({
            ...comment,
            replies: buildCommentTree(comment.id)
        }));
    }

    /**
     * Fetch comments for an item or document
     * @param {string} itemType - 'item' or 'document'
     * @param {string} itemIdentifier - Item or document identifier
     */
    async function fetchComments(itemType, itemIdentifier) {
        if (!itemType || !itemIdentifier) {
            logger.warn('fetchComments: Missing itemType or itemIdentifier');
            return;
        }

        loading.value = true;
        error.value = null;
        currentItem.value = { identifier: itemIdentifier, type: itemType };

        try {
            const response = await api.get(`/comments/${itemType}/${itemIdentifier}`);
            const fetchedComments = Array.isArray(response) ? response : [];
            // Preserve any locally-added comments that the server response doesn't yet include
            const fetchedIds = new Set(fetchedComments.map(c => c.id));
            const localOnly = comments.value.filter(c => c.id && !fetchedIds.has(c.id));
            comments.value = [...fetchedComments, ...localOnly];
        } catch (err) {
            logger.error('Failed to fetch comments:', err);
            error.value = err.message || 'Failed to load comments';
            comments.value = [];
        } finally {
            loading.value = false;
        }
    }

    /**
     * Add a new comment
     * @param {Object} commentData - Comment data
     * @param {string} commentData.content - Comment content
     * @param {number|null} commentData.parent - Parent comment ID for replies
     * @param {File|null} file - Optional attachment file
     * @returns {Object|null} - Created comment or null on failure
     */
    async function addComment(commentData, file = null) {
        if (!currentItem.value) {
            logger.error('addComment: No current item set');
            return null;
        }

        error.value = null;

        try {
            let newComment;

            if (file) {
                // Use FormData for file uploads
                const formData = new FormData();
                formData.append('content', commentData.content);
                if (commentData.parent) {
                    formData.append('parent', commentData.parent);
                }
                formData.append('file', file);

                const endpoint = currentItem.value.type === 'document'
                    ? `/comments/document/${currentItem.value.identifier}`
                    : `/comments/item/${currentItem.value.identifier}`;

                newComment = await api.upload(endpoint, formData);
            } else {
                // Use JSON for text-only comments
                const endpoint = currentItem.value.type === 'document'
                    ? `/comments/document/${currentItem.value.identifier}`
                    : `/comments/item/${currentItem.value.identifier}`;

                newComment = await api.post(endpoint, {
                    content: commentData.content,
                    parent: commentData.parent || null
                });
            }

            if (newComment) {
                comments.value.push(newComment);
            }

            return newComment;
        } catch (err) {
            logger.error('Failed to add comment:', err);
            error.value = err.message || 'Failed to add comment';
            return null;
        }
    }

    /**
     * Update an existing comment
     * @param {number} commentId - Comment ID
     * @param {string} content - Updated content
     * @returns {Object|null} - Updated comment or null on failure
     */
    async function updateComment(commentId, content) {
        error.value = null;

        try {
            const updatedComment = await api.put(`/comments/${commentId}`, { content });

            if (updatedComment) {
                const index = comments.value.findIndex(c => c.id === commentId);
                if (index !== -1) {
                    comments.value[index] = updatedComment;
                }
            }

            return updatedComment;
        } catch (err) {
            logger.error('Failed to update comment:', err);
            error.value = err.message || 'Failed to update comment';
            return null;
        }
    }

    /**
     * Delete a comment
     * @param {number} commentId - Comment ID
     * @returns {boolean} - Success status
     */
    async function deleteComment(commentId) {
        error.value = null;

        try {
            await api.delete(`/comments/delete/${commentId}`);

            // Remove the comment and all its replies
            const idsToRemove = new Set([commentId]);
            let foundNew = true;

            // Find all nested replies
            while (foundNew) {
                foundNew = false;
                for (const comment of comments.value) {
                    if (comment.parent && idsToRemove.has(comment.parent) && !idsToRemove.has(comment.id)) {
                        idsToRemove.add(comment.id);
                        foundNew = true;
                    }
                }
            }

            comments.value = comments.value.filter(c => !idsToRemove.has(c.id));

            return true;
        } catch (err) {
            logger.error('Failed to delete comment:', err);
            error.value = err.message || 'Failed to delete comment';
            return false;
        }
    }

    /**
     * Toggle upvote on a comment
     * @param {number} commentId - Comment ID
     * @returns {Object|null} - Updated comment or null on failure
     */
    async function toggleUpvote(commentId) {
        error.value = null;

        const comment = comments.value.find(c => c.id === commentId);
        if (!comment) {
            logger.error('toggleUpvote: Comment not found');
            return null;
        }

        try {
            let updatedComment;

            if (comment.user_has_upvoted) {
                // Remove upvote
                updatedComment = await api.delete(`/comments/${commentId}/upvote`);
            } else {
                // Add upvote
                updatedComment = await api.post(`/comments/${commentId}/upvote`, {});
            }

            if (updatedComment) {
                const index = comments.value.findIndex(c => c.id === commentId);
                if (index !== -1) {
                    comments.value[index] = updatedComment;
                }
            }

            return updatedComment;
        } catch (err) {
            logger.error('Failed to toggle upvote:', err);
            error.value = err.message || 'Failed to update upvote';
            return null;
        }
    }

    /**
     * Export comments as CSV
     * @returns {string} - CSV download URL
     */
    function getExportUrl() {
        if (!currentItem.value) {
            return null;
        }
        return `/salt/case/export_comment/${currentItem.value.type}/${currentItem.value.identifier}/comment.csv`;
    }

    /**
     * Clear the current comments
     */
    function clearComments() {
        comments.value = [];
        currentItem.value = null;
        error.value = null;
    }

    return {
        // State
        comments,
        loading,
        error,
        currentItem,

        // Computed
        hasComments,
        topLevelComments,

        // Methods
        getReplies,
        buildCommentTree,
        fetchComments,
        addComment,
        updateComment,
        deleteComment,
        toggleUpvote,
        getExportUrl,
        clearComments
    };
});
