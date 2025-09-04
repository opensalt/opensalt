<template>
  <div class="association-panel">
    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i>
      Drag items from the right tree to create associations with items in the left tree
    </div>
    <div class="card">
      <div class="card-body">
        <h6>Association Instructions</h6>
        <ul class="mb-0">
          <li>Select an item in the left tree</li>
          <li>Drag an item from the right tree onto it</li>
          <li>Choose the association type in the dialog</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
// AssociationPanel component - handles association mode
// This component doesn't need any props or emits as it's just informational
</script>

<style scoped>
.association-panel {
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
