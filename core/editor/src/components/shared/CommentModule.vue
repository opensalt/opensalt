<template>
    <div class="comment-module p-3">
        <!-- Header -->
        <div class="comment-header mb-3 pb-2">
             <h4 class="comment-title">
                 <i class="bi bi-chat-dots"></i>
                 Comments
                <span v-if="commentStore.comments.length > 0" class="comment-count">
                    ({{ commentStore.comments.length }})
                </span>
            </h4>
            <button
                v-if="commentStore.hasComments"
                class="btn btn-sm btn-outline-secondary export-btn"
                @click="exportComments"
                title="Export comments as CSV"
            >
                 <i class="bi bi-download"></i> Export
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="commentStore.loading" class="comment-loading">
             <i class="bi bi-arrow-repeat bi-spin"></i> Loading comments...
        </div>

        <!-- Error State -->
        <div v-if="commentStore.error" class="comment-error alert alert-danger">
            {{ commentStore.error }}
        </div>

        <!-- Comment Input -->
        <div class="comment-input-section">
            <div class="comment-input-wrapper">
                <textarea
                    v-model="newCommentContent"
                    class="form-control comment-textarea"
                    placeholder="Add a comment..."
                    rows="3"
                    :disabled="!isLoggedIn"
                ></textarea>
                <div class="comment-input-actions">
                    <label v-if="enableAttachments" class="attachment-btn" title="Attach file">
                         <i class="bi bi-paperclip"></i>
                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFileSelect"
                            style="display: none"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                        />
                    </label>
                    <span v-if="selectedFile" class="selected-file">
                        {{ selectedFile.name }}
                        <button class="remove-file" @click="removeFile">&times;</button>
                    </span>
                    <button
                        class="btn btn-primary btn-sm submit-btn"
                        @click="submitComment"
                        :disabled="!canSubmit"
                    >
                         <i class="bi bi-send"></i> Post
                    </button>
                </div>
            </div>
            <div v-if="!isLoggedIn" class="login-prompt">
                <a href="/login">Log in</a> to post comments.
            </div>
        </div>

        <!-- Comments List -->
        <div class="comments-list">
            <template v-if="commentStore.topLevelComments.length > 0">
                <CommentItem
                    v-for="comment in commentStore.topLevelComments"
                    :key="comment.id"
                    :comment="comment"
                    :replies="commentStore.getReplies(comment.id)"
                    :is-logged-in="isLoggedIn"
                    :current-user-id="currentUserId"
                    @reply="handleReply"
                    @edit="handleEdit"
                    @delete="handleDelete"
                    @upvote="handleUpvote"
                />
            </template>
            <div v-else-if="!commentStore.loading" class="no-comments m-3">
                 <i class="bi bi-chat-square-text"></i>
                <p>No comments yet. Be the first to comment!</p>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            class="modal fade"
            id="editCommentModal"
            tabindex="-1"
            ref="editModal"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Comment</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <textarea
                            v-model="editContent"
                            class="form-control"
                            rows="4"
                        ></textarea>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="saveEdit"
                            :disabled="!editContent.trim()"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            class="modal fade"
            :class="{ show: showDeleteModal, 'd-block': showDeleteModal }"
            id="deleteCommentModal"
            tabindex="-1"
            v-if="showDeleteModal"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Comment</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="cancelDelete"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this comment?</p>
                        <p v-if="hasRepliesToDelete" class="text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            This will also delete all replies.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="cancelDelete"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="confirmDelete"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Backdrop -->
        <div v-if="showDeleteModal" class="modal-backdrop fade show" @click="cancelDelete"></div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { useCommentStore } from '../../stores/commentStore.js';
import { useSessionStore } from '../../stores/sessionStore.js';
import CommentItem from './CommentItem.vue';

const props = defineProps({
    itemType: {
        type: String,
        required: true,
        validator: (value) => ['item', 'document'].includes(value)
    },
    itemIdentifier: {
        type: String,
        required: true
    },
    enableAttachments: {
        type: Boolean,
        default: false
    }
});

// Stores
const commentStore = useCommentStore();
const sessionStore = useSessionStore();

// Refs
const newCommentContent = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);
const editModal = ref(null);
const deleteModal = ref(null);
const editContent = ref('');
const editingCommentId = ref(null);
const deletingCommentId = ref(null);
const replyToComment = ref(null);
const showDeleteModal = ref(false);

// Bootstrap modal instances
let editModalInstance = null;
let deleteModalInstance = null;

// Computed
const isLoggedIn = computed(() => sessionStore.isAuthenticated);
const currentUserId = computed(() => sessionStore.user?.id);

const canSubmit = computed(() => {
    return isLoggedIn.value && (newCommentContent.value.trim() || selectedFile.value);
});

const hasRepliesToDelete = computed(() => {
    if (!deletingCommentId.value) return false;
    const replies = commentStore.getReplies(deletingCommentId.value);
    return replies.length > 0;
});

// Watch for item changes
watch(
    () => [props.itemType, props.itemIdentifier],
    ([newType, newIdentifier]) => {
        if (newType && newIdentifier) {
            commentStore.fetchComments(newType, newIdentifier);
        }
    },
    { immediate: true }
);

// Methods
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
    }
}

function removeFile() {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

async function submitComment() {
    if (!canSubmit.value) return;

    const commentData = {
        content: newCommentContent.value.trim(),
        parent: replyToComment.value
    };

    const result = await commentStore.addComment(commentData, selectedFile.value);

    if (result) {
        newCommentContent.value = '';
        selectedFile.value = null;
        replyToComment.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
}

async function handleReply(replyData) {
    // replyData can be either a comment ID (for old behavior) or an object with parentCommentId and content
    if (typeof replyData === 'number' || typeof replyData === 'string') {
        // Legacy behavior: just set which comment we're replying to
        replyToComment.value = replyData;
        // Scroll to input
        document.querySelector('.comment-input-section')?.scrollIntoView({ behavior: 'smooth' });
    } else if (replyData && typeof replyData === 'object') {
        // New behavior: replyData contains both parentCommentId and content
        replyToComment.value = replyData.parentCommentId;

        // Submit the reply immediately
        const commentData = {
            content: replyData.content,
            parent: replyData.parentCommentId
        };

        const result = await commentStore.addComment(commentData, selectedFile.value);

        if (result) {
            newCommentContent.value = '';
            selectedFile.value = null;
            replyToComment.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    }
}

function handleEdit(comment) {
    editingCommentId.value = comment.id;
    editContent.value = comment.content;

    nextTick(() => {
        if (editModal.value) {
            if (!editModalInstance) {
                editModalInstance = new Modal(editModal.value);
            }
            editModalInstance.show();
        }
    });
}

async function saveEdit() {
    if (!editContent.value.trim() || !editingCommentId.value) return;

    const result = await commentStore.updateComment(editingCommentId.value, editContent.value.trim());

    if (result) {
        editModalInstance?.hide();
        editingCommentId.value = null;
        editContent.value = '';
    }
}

function handleDelete(commentId) {
    deletingCommentId.value = commentId;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (!deletingCommentId.value) return;

    const success = await commentStore.deleteComment(deletingCommentId.value);

    if (success) {
        showDeleteModal.value = false;
        deletingCommentId.value = null;
    }
}

function cancelDelete() {
    showDeleteModal.value = false;
    deletingCommentId.value = null;
}

async function handleUpvote(commentId) {
    await commentStore.toggleUpvote(commentId);
}

function exportComments() {
    const url = commentStore.getExportUrl();
    if (url) {
        window.location.href = url;
    }
}

// Cleanup on unmount
onMounted(() => {
    nextTick(() => {
        if (editModal.value) {
            editModalInstance = new Modal(editModal.value);
        }
        if (deleteModal.value) {
            deleteModalInstance = new Modal(deleteModal.value);
        }
    });
});
</script>

<style lang="scss" scoped>
.comment-module {
    margin-top: 20px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #dee2e6;
}

.comment-title {
    margin: 0;
    font-size: 1.1rem;
    color: #495057;

    i {
        margin-right: 8px;
        color: #6c757d;
    }
}

.comment-count {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: normal;
}

.export-btn {
    font-size: 0.8rem;
}

.comment-loading,
.no-comments {
    text-align: center;
    color: #6c757d;

    i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }
}

.comment-error {
    margin-bottom: 15px;
}

.comment-input-section {
    margin-bottom: 20px;
}

.comment-input-wrapper {
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 10px;
}

.comment-textarea {
    border: none;
    resize: vertical;
    min-height: 60px;

    &:focus {
        box-shadow: none;
        outline: none;
    }
}

.comment-input-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
    gap: 10px;
}

.attachment-btn {
    cursor: pointer;
    padding: 5px 10px;
    color: #6c757d;

    &:hover {
        color: #495057;
    }

    i {
        font-size: 1.1rem;
    }
}

.selected-file {
    flex: 1;
    font-size: 0.85rem;
    color: #495057;
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    margin-right: auto;
}

.remove-file {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 0 5px;
    font-size: 1rem;

    &:hover {
        color: #c82333;
    }
}

.submit-btn {
    white-space: nowrap;
}

.login-prompt {
    text-align: center;
    margin-top: 10px;
    font-size: 0.9rem;
    color: #6c757d;

    a {
        color: #007bff;
    }
}

.comments-list {
    margin-top: 15px;
}

// Modal styles
.modal-body {
    textarea {
        width: 100%;
        resize: vertical;
    }
}
</style>
