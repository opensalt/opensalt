<template>
  <div class="alignment-report-table d-flex flex-column h-100">
    <div class="d-flex align-items-center gap-2 mb-2">
      <input
        v-model="search"
        type="search"
        class="form-control form-control-sm w-auto"
        placeholder="Search…"
        aria-label="Search report rows"
      >
      <select
        v-model.number="pageSize"
        class="form-select form-select-sm w-auto"
        aria-label="Rows per page"
      >
        <option :value="10">
          10 per page
        </option>
        <option :value="25">
          25 per page
        </option>
        <option :value="100">
          100 per page
        </option>
        <option :value="-1">
          All
        </option>
      </select>
      <span class="text-muted small">{{ pageFirst }}–{{ pageLast }} of {{ filtered.length }}</span>
    </div>
    <div class="table-scroll flex-grow-1">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">
              identifier
            </th>
            <th scope="col">
              smartLevel
            </th>
            <th scope="col">
              humanCodingScheme
            </th>
            <th scope="col">
              fullStatement
            </th>
            <th scope="col">
              Association Type
            </th>
            <th scope="col">
              Matched humanCodingScheme
            </th>
            <th scope="col">
              Matched fullStatement
            </th>
            <th scope="col">
              Matched identifier
            </th>
            <th scope="col">
              notes
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, idx) in pageRows"
            :key="idx + '|' + row.identifier + '|' + row.matchedIdentifier + '|' + row.smartLevel"
            :class="{ 'table-row-unmatched': row.unmatchedSide === 'right' }"
          >
            <td class="text-break">
              {{ row.identifier }}
            </td>
            <td>{{ row.smartLevel }}</td>
            <td>{{ row.humanCodingScheme }}</td>
            <td class="text-break">
              {{ row.fullStatement }}
            </td>
            <td :class="{ 'text-success': row.exactMatch }">
              {{ row.associationType }}
            </td>
            <td>{{ row.matchedHumanCodingScheme }}</td>
            <td class="text-break diff-cell">
              <template v-if="row.diffSegments && row.diffSegments.length">
                <span
                  v-for="(seg, i) in row.diffSegments"
                  :key="i"
                  :class="{ 'diff-added': seg.added, 'diff-removed': seg.removed }"
                >{{ seg.value }}</span>
              </template>
              <template v-else>
                {{ row.matchedFullStatement }}
              </template>
            </td>
            <td class="text-break">
              {{ row.matchedIdentifier }}
            </td>
            <td>{{ row.notes }}</td>
          </tr>
          <tr v-if="pageRows.length === 0">
            <td
              colspan="9"
              class="text-center text-muted py-4"
            >
              No data available
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <nav
      v-if="totalPages > 1"
      class="d-flex align-items-center justify-content-end gap-2 mt-2"
      aria-label="Report pagination"
    >
      <ul class="pagination pagination-sm mb-0">
        <li
          class="page-item"
          :class="{ disabled: page <= 1 }"
        >
          <button
            type="button"
            class="page-link"
            data-testid="pager-prev"
            :disabled="page <= 1"
            @click="goTo(page - 1)"
          >
            Previous
          </button>
        </li>
        <li class="page-item">
          <span class="page-link d-inline-flex align-items-center gap-1">
            Page
            <input
              v-model.number="pageInput"
              type="number"
              min="1"
              :max="totalPages"
              class="form-control form-control-sm page-jump-input"
              data-testid="pager-input"
              aria-label="Go to page"
              @change="jumpTo(pageInput)"
              @keyup.enter="jumpTo(pageInput)"
            >
            of {{ totalPages }}
          </span>
        </li>
        <li
          class="page-item"
          :class="{ disabled: page >= totalPages }"
        >
          <button
            type="button"
            class="page-link"
            data-testid="pager-next"
            :disabled="page >= totalPages"
            @click="goTo(page + 1)"
          >
            Next
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
});

const search = ref('');
const pageSize = ref(10);
const page = ref(1);
// Editable mirror of `page` for the jump-to-page input; kept in sync both ways.
const pageInput = ref(1);

watch([search, pageSize], () => {
  page.value = 1;
});

watch(page, (current) => {
  pageInput.value = current;
});

const filtered = computed(() => {
  const needle = search.value.trim().toLowerCase();
  if (!needle) {
    return props.rows;
  }
  return props.rows.filter((r) =>
    [
      r.identifier,
      r.humanCodingScheme,
      r.fullStatement,
      r.associationType,
      r.matchedHumanCodingScheme,
      r.matchedFullStatement,
      r.matchedIdentifier,
    ].some((v) => String(v ?? '').toLowerCase().includes(needle)),
  );
});

const totalPages = computed(() =>
  pageSize.value === -1 ? 1 : Math.max(1, Math.ceil(filtered.value.length / pageSize.value)),
);

// Keep the page in range when filtering shrinks the result set.
watch(totalPages, (pages) => {
  if (page.value > pages) {
    page.value = pages;
  }
});

const pageRows = computed(() => {
  if (pageSize.value === -1) {
    return filtered.value;
  }
  const start = (page.value - 1) * pageSize.value;
  return filtered.value.slice(start, start + pageSize.value);
});

const pageFirst = computed(() => {
  if (filtered.value.length === 0) {
    return 0;
  }
  return pageSize.value === -1 ? 1 : (page.value - 1) * pageSize.value + 1;
});
const pageLast = computed(() => (pageSize.value === -1 ? filtered.value.length : Math.min(page.value * pageSize.value, filtered.value.length)));

function goTo(target) {
  page.value = Math.min(Math.max(1, target), totalPages.value);
}

function jumpTo(target) {
  const parsed = Number.parseInt(target, 10);
  goTo(Number.isNaN(parsed) ? page.value : parsed);
  pageInput.value = page.value;
}
</script>

<style scoped>
.table-scroll {
  min-height: 0;
  overflow: auto;
}
/* Pager: separate segments and give Previous/Next/Page the same height.
   Buttons hover like the shell's btn-outline-primary view buttons. */
.pagination {
  gap: 0.375rem;
}
.pagination button.page-link {
  color: var(--bs-primary);
  border-color: var(--bs-primary);
  background: var(--bs-body-bg);
}
.pagination button.page-link:not(:disabled):hover,
.pagination button.page-link:not(:disabled):focus {
  background: var(--bs-primary);
  border-color: var(--bs-primary);
  color: #fff;
  z-index: 2;
}
.pagination button.page-link:disabled {
  color: var(--bs-secondary-color);
  background: var(--bs-secondary-bg);
  border-color: var(--bs-border-color);
}
.pagination .page-link {
  display: inline-flex;
  align-items: center;
  min-height: calc(1.8125rem + 2px);
  padding-top: 0.125rem;
  padding-bottom: 0.125rem;
}
.page-jump-input {
  width: 4.5rem;
  height: 1.5rem;
  min-height: 1.5rem;
  padding: 0 0.375rem;
  font-size: 0.875rem;
  text-align: center;
}
thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #fff;
  box-shadow: inset 0 -1px 0 #dee2e6;
}
.diff-added {
  color: #198754;
  text-decoration: underline;
}
.diff-removed {
  color: #dc3545;
  text-decoration: line-through;
}
.table-row-unmatched {
  background-color: #f8f9fa;
  font-style: italic;
}
</style>
