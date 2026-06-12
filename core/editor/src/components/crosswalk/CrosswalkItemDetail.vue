<template>
  <div class="crosswalk-item-detail card mb-2">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6>Origin Item</h6>
          <p class="mb-1">
            <strong>{{ association.originNodeURI?.title || 'Unknown' }}</strong>
          </p>
          <p class="text-muted small">
            {{ association.originNodeURI?.identifier }}
          </p>
        </div>
        <div class="col-md-6">
          <h6>Destination Item</h6>
          <p class="mb-1">
            <strong>{{ association.destinationNodeURI?.title || 'Unknown' }}</strong>
          </p>
          <p class="text-muted small">
            {{ association.destinationNodeURI?.identifier }}
          </p>
        </div>
      </div>
      <div class="mt-2">
        <span
          class="badge me-2"
          :class="typeClass"
        >{{ association.associationType }}</span>
        <span class="badge bg-secondary">{{ association.extensions?.['crosswalk:subtype'] || 'related' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  association: {
    type: Object,
    required: true,
  },
});

const typeClass = computed(() => {
  if (props.association.associationType === 'exactMatchOf') return 'bg-success';
  return 'bg-info';
});
</script>
