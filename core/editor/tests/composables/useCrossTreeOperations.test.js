import { describe, it, expect, vi } from 'vitest';
import { useCrossTreeOperations } from '../../src/composables/useCrossTreeOperations';
import { ref } from 'vue';

describe('useCrossTreeOperations', () => {
  describe('handleTreeChange', () => {
    it('should return internal move when in itemDetails mode and dragging within same document', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('itemDetails');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc1' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({
        isInternal: true,
        draggedItem: event.draggedItem,
        targetItem: event.targetItem,
        position: event.position
      });
    });

    it('should create association when in createAssociations mode even with same document', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('createAssociations');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc1' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({ isInternal: false, action: 'associate' });
      expect(associationOrigin.value).toEqual(event.draggedItem);
      expect(associationDestination.value).toEqual(event.targetItem);
    });

    it('should create association when in createAssociations mode with different documents', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('createAssociations');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc2' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({ isInternal: false, action: 'associate' });
      expect(associationOrigin.value).toEqual(event.draggedItem);
      expect(associationDestination.value).toEqual(event.targetItem);
    });

    it('should copy when in copyItems mode even with same document', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('copyItems');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange, showCrossTreeModal, crossTreeSource, crossTreeTarget, crossTreePosition } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc1' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({ isInternal: false, action: 'copy' });
      expect(showCrossTreeModal.value).toBe(true);
      expect(crossTreeSource.value).toEqual(event.draggedItem);
      expect(crossTreeTarget.value).toEqual(event.targetItem);
      expect(crossTreePosition.value).toEqual(event.position);
    });

    it('should copy when in copyItems mode with different documents', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('copyItems');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange, showCrossTreeModal, crossTreeSource, crossTreeTarget, crossTreePosition } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc2' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({ isInternal: false, action: 'copy' });
      expect(showCrossTreeModal.value).toBe(true);
      expect(crossTreeSource.value).toEqual(event.draggedItem);
      expect(crossTreeTarget.value).toEqual(event.targetItem);
      expect(crossTreePosition.value).toEqual(event.position);
    });

    it('should prompt for action in itemDetails mode with different documents', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('itemDetails');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange, showCrossTreeModal, crossTreeSource, crossTreeTarget, crossTreePosition } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc2' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toEqual({ isInternal: false, action: 'prompt' });
      expect(showCrossTreeModal.value).toBe(true);
      expect(crossTreeSource.value).toEqual(event.draggedItem);
      expect(crossTreeTarget.value).toEqual(event.targetItem);
      expect(crossTreePosition.value).toEqual(event.position);
    });

    it('should return null when event type is not move', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('itemDetails');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'copy',
        draggedItem: { identifier: 'item1', documentId: 'doc1' },
        targetItem: { identifier: 'item2', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toBeNull();
    });

    it('should return undefined when dragging item onto itself', async () => {
      // Arrange
      const currentDoc = ref({ id: 'doc1' });
      const rightPanelMode = ref('itemDetails');
      const associationOrigin = ref(null);
      const associationDestination = ref(null);

      const { handleTreeChange } = useCrossTreeOperations({
        currentDoc,
        rightPanelMode,
        associationOrigin,
        associationDestination,
      });

      const event = {
        type: 'move',
        draggedItem: { identifier: 'item1', documentId: 'doc1' },
        targetItem: { identifier: 'item1', documentId: 'doc1' },
        position: 'before'
      };

      // Act
      const result = await handleTreeChange(event);

      // Assert
      expect(result).toBeUndefined();
    });
  });
});
