# AGENTS.md

> **Repository:** https://github.com/opensalt/opensalt
> **Purpose:** This file tells agents how to work productively and safely in this codebase: what tasks it may perform, which commands to run, our coding and PR conventions, and the guardrails that must be respected.

## 0) TL;DR
- **Environment:** Docker + Docker Compose; Vuejs 3 application for viewing and editing frameworks
- **Build & run (local/CI containers):**
  - `npm run dev` — start development application
  - `npm install` - install dependencies
  - `npm run build` — build application
- **Testing & quality (do in this order):**
  1. `npm run test` — run all tests with Vitest
  2. `npm run test:watch` — run tests in watch mode
  3. `npm run test:coverage` — run tests with coverage report
  4. `npm run lint` — run ESLint
- **Typical PR workflow:** create branch `feature/<scope>-<short-desc>`, commit in small diffs, open PR linked to an issue, include migration notes, test results, and a brief change log (see §6).
- **Guardrails:** do **not** commit secrets, DB data, or container volumes; prefer minimal diffs; never bypass CI failures; ask for human review for schema or API changes (see §7).  Do **NOT** commit anything unless explicitly requested, let the developer manage git.

---

## 1) Architecture: Modal system (READ BEFORE TOUCHING MODALS)

The editor uses a **centralized modal system**. Understanding the event chain is essential — mistakes here cause subtle, hard-to-debug bugs.

### Components involved

| Layer | File | Responsibility |
|-------|------|----------------|
| State composable | `src/composables/useModalState.js` | Owns all modal `show*` refs + data refs + open/close functions |
| Modal renderer | `src/components/tree/ModalManager.vue` | Renders every modal; receives props from parent, forwards emits |
| Top-level editor | `src/components/tree/EnhancedDocumentTreeEditor.vue` | Wires `useModalState()`, passes props to `ModalManager`, handles events |
| Individual modals | `src/components/tree/modals/*.vue` | Self-contained modal components with `show` prop and `hidden` emit |

### Event chain (critical to get right)

```
Child panel (e.g. ItemDetails.vue)
  └─ emits event (e.g. 'view-json', 'edit-item')
     └─ ItemDetailsPanel.vue forwards the emit
        └─ RightSidePanel.vue forwards the emit
           └─ EnhancedDocumentTreeEditor.vue
              └─ calls modalState.openXxxModal(data)
                 └─ ModalManager.vue renders the modal
```

**Panels must never call `useModalState()` directly.** Each call to `useModalState()` creates **independent refs** — they do not share state. Only `EnhancedDocumentTreeEditor.vue` should call `useModalState()`. Panels emit events that bubble up; the editor calls the open/close methods.

### Modal component pattern (Bootstrap Modal JS API)

Every modal follows the `ExportModal.vue` pattern:

```vue
<script setup>
import { ref, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

const props = defineProps({
  show: Boolean,
  /* other props */
});
const emit = defineEmits(['hidden', /* other emits */]);

const modalElement = ref(null);
let bsModal = null;

onMounted(() => {
  bsModal = new Modal(modalElement.value);
  modalElement.value.addEventListener('hidden.bs.modal', () => emit('hidden'));
});

watch(() => props.show, (val) => {
  if (!bsModal) return;
  val ? bsModal.show() : bsModal.hide();
});
</script>
```

- **`show` prop** (Boolean): controlled by parent; parent flips it to `true` then waits for the `hidden` emit to flip it back to `false`.
- **`hidden` emit**: fired when Bootstrap finishes hiding; the parent's handler resets `show` to `false`.

### Adding a new modal

1. Create `src/components/tree/modals/MyModal.vue` following the pattern above.
2. In `useModalState.js`: add `showMyModal` ref, a data ref if needed, `openMyModal()` / `closeMyModal()` functions, add them to the `return` statement and `resetModalData()`.
3. In `ModalManager.vue`: lazy-import the component, add it to the template with `:show`/data props/`@hidden`, add the `show*` prop to `defineProps`, add `<name>-modal-hidden` to `defineEmits`, and add an `onMyModalHidden()` handler.
4. In `EnhancedDocumentTreeEditor.vue`: destructure the new refs/functions from `modalState`, pass the `:show-*` props to `<ModalManager>`, add the `@*-modal-hidden` handler, and add an `onMyXxx()` trigger function.
5. In the child panel: **emit an event** (do not call `useModalState()`). Forward it through `ItemDetailsPanel` → `RightSidePanel` → `EnhancedDocumentTreeEditor`.

### Lazy loading

All modals in `ModalManager.vue` use `defineAsyncComponent()` so they are code-split and only loaded when first shown.

---

## 2) Architecture: API service

All HTTP requests go through the singleton `api` instance from `src/services/api.js`.

- **Base URL:** relative (same host); `this.baseUrl = ''`.
- **Auth:** `Authorization: Bearer <token>` from `localStorage.getItem('saltApiToken')` (optional for public content).
- **Usage:** `import { api } from '@/services/api.js'` then `await api.get(endpoint)`.
- **Error handling:** throws `ApiError` with `.status` and `.message`.

### Key CASE v1.1 endpoints

| Purpose | Endpoint |
|---------|----------|
| Single document (CASE JSON) | `GET /ims/case/v1p1/CFDocuments/{identifier}` |
| Single item (CASE JSON) | `GET /ims/case/v1p1/CFItems/{identifier}` |
| Document + all children (package) | `GET /ims/case/v1p1/CFPackages/{id}.json` |
| Related documents | `GET /ims/case/v1p1/CFDocuments/{id}/related` |

> **Note:** The editor uses its own non-CASE endpoints for most tree/item operations (`/framework/editor/...`). Use CASE endpoints only when you need the spec-compliant JSON representation.

---

## 3) Tests

- **Framework:** Vitest with Vue Test Utils
- **Test structure:**
  - `tests/services/` — API service tests
  - `tests/stores/` — Pinia store tests (documentStore, itemStore, associationStore)
  - `tests/components/` — Vue component tests
- **Run tests:**
  - `npm run test` — run all tests
  - `npm run test:watch` — interactive watch mode
  - `npm run test:coverage` — generate coverage report
- **Writing tests:**
  - Mirror patterns from `core/tests/` (PHP tests)
  - Use `@vue/test-utils` for component mounting
  - Mock API calls with `vi.mock('@/services/api.js')`
  - Use `setActivePinia(createPinia())` for store tests

> **Agent rule:** Add/adjust tests for any logic you change. Run tests before committing.

---

## 6) Branching, commits, and PRs

- **Branch name:** `feature/<type>-<scope>-<short-desc>`
  _Examples: `feature/fix-core-query-npe`, `feature/chore-php-cs-fixer`, `feature/feat-api-case11-extensions`_
- **Commit style:** conventional-ish, present tense, scoped, ≤ 72 char subject.
- **PR checklist (must include):**
  - Problem summary & approach
  - Changed areas (files/modules)
  - Any schema changes + migration ID(s)
  - Tests added/updated + pass/fail summary
  - Local run instructions if needed
  - Screenshots/GIFs for UI changes
  - Risk & rollback notes
- **Link issues:** auto‑close with `Fixes #<id>` when appropriate.
- **Labels:** apply `bug`, `enhancement`, `chore`, `docs`, `tech-debt`, or `security` as relevant.

---

## 7) Guardrails (hard rules)

1. **No secrets** in code, config, or PR text.
2. **Minimal diffs:** limit changes to the files and lines required.
3. **No wide‑formatting** sweeps; restrict formatters to changed files.
4. **Do not** modify `LICENSE`, `NOTICE`, or repository‑level legal text.
5. **Database:** never commit data/volume content; migrations require human review.
6. **External APIs/specs:** do not reinterpret protocol/spec behavior without a maintainer sign‑off.
7. **CI is the authority:** do not mark build green if tests or linters fail.
8. **Ask a human** for: cross‑service refactors, public API changes, schema changes, dependency major upgrades.

---

## 8) Common tasks agents may perform

### A) Bug fix in `core/` (service/controller/entity)
1. Reproduce if possible (log steps in PR).
2. Write/adjust unit/feature tests.
3. Implement minimal fix.
4. Run formatters & tests (see §3–4).
5. Open PR with checklist (see §6).

### B) Lint/format drift
- Run PHP ecs only on changed files; avoid repo‑wide formatting unless asked.

### C) Dependency patch/minor updates
- Update Composer constraints conservatively.
- Rebuild, migrate if needed, run tests.
- Document notable changes and link release notes.

### D) Documentation update
- When commands/flows change, update `README.md` and any relevant `UPGRADE_*` doc.
- Include before/after snippets in PR.

---

## 9) Domain context: CASE & standards (for planning)
- OpenSALT implements/works with the IMS Global **CASE** specification and related education standards artifacts.
- When changing model, API, or importer/exporter logic, ensure compatibility with existing CASE content and add tests for expected objects/associations.

> **Agent behavior:** prefer additive, backward‑compatible changes; consult maintainers before altering any on‑disk format, API contract, or CASE mapping.

---

## 10) Observability & local troubleshooting

- **Symfony logs:** `core/var/log/` inside the `web` container
- **Cache clear/warmup:**
  `docker compose exec web ./core/bin/console cache:clear --env=dev`
  `docker compose exec web ./core/bin/console cache:warmup --env=dev`
- **Health checks:** app should respond on web container root once `make up` + `make force-build` complete.

---

## 11) Maintainers & reviews
- **Code owner review required** for schema, API, or security‑sensitive changes.
- If unsure, request review from the `@roverwolf` and leave the PR in **draft** until feedback.

---

## 12) What agents should **not** do
- Create or modify GitHub repo‑wide settings, branch protections, or CI pipelines.
- Introduce new services/containers without an explicit ticket.
- Reorganize directory layout or namespaces unprompted.
- Change license headers or notices.

---

*Thank you. Keep PRs small; keep tests green; ask when in doubt.*

