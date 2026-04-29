<template>
  <div class="copy-items-panel">
    <div class="alert alert-info">
      <i class="bi bi-info-circle" />
      Drag items from the right tree to copy them to the left tree
    </div>
    <div class="card">
      <div class="card-body">
        <h6>Copy Instructions</h6>
        <ul class="mb-0">
          <li>Select items in the right tree</li>
          <li>Drag them to the desired location in the left tree</li>
          <li>The items will be copied with their associations</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
// CopyItemsPanel component - handles copy items mode
// This component doesn't need any props or emits as it's just informational
</script>

<style scoped>
.copy-items-panel {
  padding: 1rem;
}

.alert {
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
}

.card-body {
  padding: 1rem;
}

.card-body ul {
  padding-left: 1.5rem;
}

.card-body li {
  margin-bottom: 0.5rem;
}
</style>
