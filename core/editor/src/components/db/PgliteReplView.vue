<template>
  <section class="db-repl-view d-flex flex-column h-100">
    <div class="alert alert-info py-2 px-3 mb-3 small" role="status">
      PGlite REPL sandbox at <code>{{ databasePath }}</code>.
    </div>

    <div v-if="errorMessage" class="alert alert-danger py-2 px-3 mb-3" role="alert">
      {{ errorMessage }}
    </div>

    <div class="repl-shell flex-grow-1">
      <pglite-repl ref="replElement" class="repl-element"></pglite-repl>
    </div>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import '@electric-sql/pglite-repl/webcomponent';
import { createPgliteClient } from '../../db/pgliteClient.js';
import { DEFAULT_DATA_DIR } from '../../db/pgliteDataDir.js';

const replElement = ref(null);
const errorMessage = ref('');
const databasePath = DEFAULT_DATA_DIR;

let pg = null;
const client = createPgliteClient();

onMounted(async () => {
  try {
    pg = client.createReplInterface({ dataDir: databasePath });
    await nextTick();
    if (!replElement.value) {
      throw new Error('PGlite REPL element is not available');
    }

    replElement.value.pg = pg;
  } catch (error) {
    errorMessage.value = `Unable to initialize PGlite REPL: ${error instanceof Error ? error.message : 'Unknown error'}`;
  }
});

onBeforeUnmount(() => {
  pg = null;
});
</script>

<style scoped>
.db-repl-view {
  min-height: 0;
}

.repl-shell {
  min-height: 0;
}

.repl-element {
  display: block;
  height: 100%;
}
</style>
