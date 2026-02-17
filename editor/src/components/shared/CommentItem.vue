<template>
    <div class="comment-item" :class="{ 'is-reply': isReply }">
        <div class="comment-avatar">
            <div class="avatar-placeholder">
                {{ avatarInitials }}
            </div>
        </div>

        <div class="comment-content">
            <div class="comment-header">
                <span class="comment-author">{{ comment.fullname }}</span>
                <span class="comment-time">{{ formattedTime }}</span>
            </div>

            <!-- Comment Body -->
            <div v-if="!isEditing" class="comment-body">
                <p>{{ comment.content }}</p>

                <!-- File Attachment -->
                <div v-if="comment.file_url" class="comment-attachment">
                    <a :href="comment.file_url" target="_blank" class="attachment-link">
                        <i :class="fileIcon"></i>
                        {{ attachmentName }}
                    </a>
                </div>
            </div>

            <!-- Edit Form -->
            <div v-else class="comment-edit-form">
                <textarea
                    v-model="editContent"
                    class="form-control"
                    rows="3"
                ></textarea>
                <div class="edit-actions">
                    <button class="btn btn-sm btn-secondary" @click="cancelEdit">Cancel</button>
                    <button class="btn btn-sm btn-primary" @click="saveEdit" :disabled="!editContent.trim()">
                        Save
                    </button>
                </div>
            </div>

            <!-- Comment Actions -->
            <div v-if="!isEditing" class="comment-actions">
                <!-- Upvote -->
                <button
                    class="action-btn upvote-btn"
                    :class="{ 'has-upvoted': comment.user_has_upvoted }"
                    @click="$emit('upvote', comment.id)"
                    :disabled="!isLoggedIn"
                    :title="comment.user_has_upvoted ? 'Remove upvote' : 'Upvote'"
                >
                    <i class="fas fa-thumbs-up"></i>
                    <span v-if="comment.upvote_count > 0" class="upvote-count">
                        {{ comment.upvote_count }}
                    </span>
                </button>

                <!-- Reply -->
                <button
                    v-if="!isReply"
                    class="action-btn reply-btn"
                    @click="toggleReply"
                    :disabled="!isLoggedIn"
                    title="Reply"
                >
                    <i class="fas fa-reply"></i> Reply
                </button>

                <!-- Edit (only for comment author) -->
                <button
                    v-if="isAuthor"
                    class="action-btn edit-btn"
                    @click="startEdit"
                    title="Edit"
                >
                    <i class="fas fa-edit"></i> Edit
                </button>

                <!-- Delete (only for comment author) -->
                <button
                    v-if="isAuthor"
                    class="action-btn delete-btn"
                    @click="$emit('delete', comment.id)"
                    title="Delete"
                >
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>

            <!-- Reply Form -->
            <div v-if="showReplyForm && !isEditing" class="reply-form">
                <textarea
                    v-model="replyContent"
                    class="form-control"
                    placeholder="Write a reply..."
                    rows="2"
                ></textarea>
                <div class="reply-actions">
                    <button class="btn btn-sm btn-secondary" @click="cancelReply">Cancel</button>
                    <button
                        class="btn btn-sm btn-primary"
                        @click="submitReply"
                        :disabled="!replyContent.trim()"
                    >
                        Reply
                    </button>
                </div>
            </div>

            <!-- Nested Replies -->
            <div v-if="replies.length > 0" class="comment-replies">
                <CommentItem
                    v-for="reply in replies"
                    :key="reply.id"
                    :comment="reply"
                    :replies="[]"
                    :is-logged-in="isLoggedIn"
                    :current-user-id="currentUserId"
                    :is-reply="true"
                    @reply="$emit('reply', $event)"
                    @edit="$emit('edit', $event)"
                    @delete="$emit('delete', $event)"
                    @upvote="$emit('upvote', $event)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    comment: {
        type: Object,
        required: true
    },
    replies: {
        type: Array,
        default: () => []
    },
    isLoggedIn: {
        type: Boolean,
        default: false
    },
    currentUserId: {
        type: [Number, String],
        default: null
    },
    isReply: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['reply', 'edit', 'delete', 'upvote']);

// State
const showReplyForm = ref(false);
const replyContent = ref('');
const isEditing = ref(false);
const editContent = ref('');

// Computed
const avatarInitials = computed(() => {
    const name = props.comment.fullname || 'Unknown';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

const formattedTime = computed(() => {
    const date = new Date(props.comment.created);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 7) {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } else if (days > 0) {
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else if (hours > 0) {
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (minutes > 0) {
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else {
        return 'Just now';
    }
});

const isAuthor = computed(() => {
    return props.comment.created_by_current_user === true;
});

const fileIcon = computed(() => {
    const mimeType = props.comment.file_mime_type || '';
    if (mimeType.startsWith('image/')) {
        return 'fas fa-image';
    } else if (mimeType.includes('pdf')) {
        return 'fas fa-file-pdf';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return 'fas fa-file-word';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return 'fas fa-file-excel';
    } else {
        return 'fas fa-file';
    }
});

const attachmentName = computed(() => {
    const url = props.comment.file_url || '';
    const parts = url.split('/');
    return parts[parts.length - 1] || 'Attachment';
});

// Methods
function toggleReply() {
    showReplyForm.value = !showReplyForm.value;
    if (!showReplyForm.value) {
        replyContent.value = '';
    }
}

function cancelReply() {
    showReplyForm.value = false;
    replyContent.value = '';
}

function submitReply() {
    if (!replyContent.value.trim()) return;

    emit('reply', props.comment.id);
    // The parent will handle the actual reply submission
    // We just emit the event and reset the form
    showReplyForm.value = false;
    replyContent.value = '';
}

function startEdit() {
    isEditing.value = true;
    editContent.value = props.comment.content;
}

function cancelEdit() {
    isEditing.value = false;
    editContent.value = '';
}

function saveEdit() {
    if (!editContent.value.trim()) return;

    emit('edit', {
        id: props.comment.id,
        content: editContent.value.trim()
    });
    isEditing.value = false;
}
</script>

<style lang="scss" scoped>
.comment-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;

    &:last-child {
        border-bottom: none;
    }

    &.is-reply {
        padding: 10px 0;
        background: #f8f9fa;
        margin: 8px 0;
        border-radius: 6px;
        padding: 10px 12px;
        border-bottom: none;
    }
}

.comment-avatar {
    flex-shrink: 0;
}

.avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;

    .is-reply & {
        width: 32px;
        height: 32px;
        font-size: 0.75rem;
    }
}

.comment-content {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.comment-author {
    font-weight: 600;
    color: #343a40;
}

.comment-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.comment-body {
    p {
        margin: 0;
        color: #495057;
        line-height: 1.5;
        word-wrap: break-word;
    }
}

.comment-attachment {
    margin-top: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #007bff;
    text-decoration: none;
    font-size: 0.9rem;

    &:hover {
        text-decoration: underline;
    }

    i {
        font-size: 1.1rem;
    }
}

.comment-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.action-btn {
    background: none;
    border: none;
    padding: 4px 8px;
    font-size: 0.8rem;
    color: #6c757d;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 4px;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: #e9ecef;
        color: #495057;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    i {
        font-size: 0.85rem;
    }
}

.upvote-btn {
    &.has-upvoted {
        color: #28a745;

        &:hover:not(:disabled) {
            background: #d4edda;
            color: #28a745;
        }
    }
}

.upvote-count {
    font-weight: 600;
}

.reply-form,
.comment-edit-form {
    margin-top: 12px;
    padding: 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.reply-actions,
.edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
}

.comment-replies {
    margin-top: 12px;
    padding-left: 12px;
    border-left: 2px solid #e9ecef;
}

// Dark mode support
@media (prefers-color-scheme: dark) {
    .comment-item.is-reply {
        background: #2d3748;
    }

    .comment-author {
        color: #f7fafc;
    }

    .comment-body p {
        color: #e2e8f0;
    }

    .comment-attachment {
        background: #2d3748;
        border-color: #4a5568;
    }

    .action-btn:hover:not(:disabled) {
        background: #4a5568;
        color: #f7fafc;
    }

    .reply-form,
    .comment-edit-form {
        background: #2d3748;
        border-color: #4a5568;
    }

    .comment-replies {
        border-left-color: #4a5568;
    }
}
</style>
