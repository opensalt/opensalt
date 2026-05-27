
<template>
  <!-- Backdrop -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
  <div
    v-if="props.show"
    class="modal-backdrop fade"
    :class="{ 'show': props.show }"
    @click="closeModal"
  />

  <!-- Modal -->
  <div
    id="addNewOrganizationModal"
    class="modal fade"
    :class="{ 'show d-block': props.show }"
    tabindex="-1"
    aria-hidden="true"
    :style="{ display: props.show ? 'block' : 'none' }"
  >
    <div
      class="modal-dialog modal-xl"
      role="document"
      style="width:99%"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="addNewOrganizationModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Organization' : 'Add New Organization' }}
          </h5>
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="closeModal"
          />
        </div>
        <div class="modal-body">
          <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            >
              <span class="visually-hidden">Loading form...</span>
            </div>
          </div>
          <div
            v-else-if="error"
            class="alert alert-danger"
            role="alert"
          >
            {{ error }}
          </div>
          <form
            v-else
            name="org_form"
            @submit.prevent="createItem"
          >
            <div class="row mb-3">
              <label
                for="org_name"
                class="col-sm-2 col-form-label required-label"
              >Name</label>
              <div class="col-sm-10">
                <input
                  id="org_name"
                  v-model="formData.name"
                  type="text"
                  class="form-control"
                  name="org[name]"
                  required
                  placeholder="Enter organization name"
                >
                <small class="text-muted">Name or title of the organization.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_description"
                class="col-sm-2 col-form-label required-label"
              >Description</label>
              <div class="col-sm-10">
                <textarea
                  id="org_description"
                  v-model="formData.description"
                  class="form-control"
                  name="org[description]"
                  rows="3"
                  placeholder="Enter description"
                  required
                />
                <small class="text-muted">Description of the organization.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_type"
                class="col-sm-2 col-form-label"
              >Organization Type</label>
              <div class="col-sm-10">
                <select
                  id="org_type"
                  v-model="formData.type"
                  class="form-select"
                  name="org[type]"
                >
                  <option value="">
                    Select Organization Type
                  </option>
                  <option value="orgType:AccreditationBody">
                    Accreditation Body
                  </option>
                  <option value="orgType:AssessmentBody">
                    Assessment Body
                  </option>
                  <option value="orgType:Business">
                    Business
                  </option>
                  <option value="orgType:BusinessAssociation">
                    Business or Industry Association
                  </option>
                  <option value="orgType:CertificationBody">
                    Certification Body
                  </option>
                  <option value="orgType:Collaborative">
                    Collaborative
                  </option>
                  <option value="orgType:CoordinatingBody">
                    Coordinating Body
                  </option>
                  <option value="orgType:FourYear">
                    Four-Year College
                  </option>
                  <option value="orgType:Government">
                    Government Agency
                  </option>
                  <option value="orgType:HighSchool">
                    High School
                  </option>
                  <option value="orgType:LaborUnion">
                    Labor Union
                  </option>
                  <option value="orgType:LowerSecondarySchool">
                    Lower Secondary School
                  </option>
                  <option value="orgType:Magnet">
                    Magnet/Competitive Admissions School
                  </option>
                  <option value="orgType:Military">
                    Military
                  </option>
                  <option value="orgType:NonTraditional">
                    Alternative/Non-Traditional School
                  </option>
                  <option value="orgType:Postsecondary">
                    Postsecondary Educational Institution
                  </option>
                  <option value="orgType:PrimarilyOnline">
                    Primarily Online
                  </option>
                  <option value="orgType:PrimarySchool">
                    Primary School
                  </option>
                  <option value="orgType:ProfessionalAssociation">
                    Professional Association
                  </option>
                  <option value="orgType:QualityAssurance">
                    Quality Assurance Body
                  </option>
                  <option value="orgType:Regulator">
                    Regulator
                  </option>
                  <option value="orgType:SecondarySchool">
                    Secondary School
                  </option>
                  <option value="orgType:Technical">
                    Career and Technical School
                  </option>
                  <option value="orgType:TrainingProvider">
                    Education and Training Provider
                  </option>
                  <option value="orgType:TwoYear">
                    Two-Year College
                  </option>
                  <option value="orgType:UpperSecondarySchool">
                    Upper Secondary School
                  </option>
                  <option value="orgType:Vendor">
                    Vendor
                  </option>
                </select>
                <small class="text-muted">The type of organization.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_logo"
                class="col-sm-2 col-form-label"
              >Logo URI</label>
              <div class="col-sm-10">
                <input
                  id="org_logo"
                  v-model="formData.logo"
                  type="text"
                  class="form-control"
                  name="org[logo]"
                  placeholder="Enter logo URL"
                >
                <small class="text-muted">The organization's logo.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_legalName"
                class="col-sm-2 col-form-label"
              >Legal Name</label>
              <div class="col-sm-10">
                <input
                  id="org_legalName"
                  v-model="formData.legalName"
                  type="text"
                  class="form-control"
                  name="org[legalName]"
                  placeholder="Enter legal name"
                >
                <small class="text-muted">The organization's legal name.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_ctid"
                class="col-sm-2 col-form-label"
              >CTID</label>
              <div class="col-sm-10">
                <input
                  id="org_ctid"
                  v-model="formData.ctid"
                  type="text"
                  class="form-control"
                  name="org[ctid]"
                  placeholder="Enter CTID"
                >
                <small class="text-muted">The organization's CTID.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_webpage"
                class="col-sm-2 col-form-label"
              >Webpage</label>
              <div class="col-sm-10">
                <input
                  id="org_webpage"
                  v-model="formData.webpage"
                  type="url"
                  class="form-control"
                  name="org[webpage]"
                  placeholder="Enter webpage URL"
                >
                <small class="text-muted">Webpage that describes this organization.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="org_jurisdiction"
                class="col-sm-2 col-form-label"
              >Jurisdiction</label>
              <div class="col-sm-10">
                <input
                  id="org_jurisdiction"
                  v-model="formData.jurisdiction"
                  type="text"
                  class="form-control"
                  name="org[jurisdiction]"
                  placeholder="e.g., Province, Country"
                >
                <small class="text-muted">Geographic or political region of the organization.</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="closeModal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="saving"
            @click="saveItem"
          >
            <span
              v-if="saving"
              class="spinner-border spinner-border-sm me-2"
              role="status"
            />
            {{ isEdit ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';

const props = defineProps({
  parentItem: {
    type: Object,
    default: null
  },
  itemType: {
    type: String,
    default: ''
  },
  show: {
    type: Boolean,
    default: false
  },
  item: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);

const isEdit = computed(() => !!props.item);

const formData = reactive({
  name: '',
  description: '',
  type: '',
  logo: '',
  legalName: '',
  ctid: '',
  webpage: '',
  jurisdiction: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

watch(() => props.item, (newItem) => {
  if (newItem) {
    loadFormData();
  }
}, { immediate: true });

watch(() => props.itemType, (newType) => {
  if (newType && !isEdit.value) {
    formData.type = newType;
  }
}, { immediate: true });

function loadFormData() {
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    // Map CASE properties to form fields
    // abbreviatedStatement maps to name
    formData.name = props.item.abbreviatedStatement || '';
    // fullStatement maps to description
    formData.description = props.item.fullStatement || '';
    // ceterms:agentType maps to type
    formData.type = props.item.extensions?.['ceterms:agentType'] || '';
    // ceterms:image maps to logo
    formData.logo = props.item.extensions?.['ceterms:image'] || '';
    // sdo:legalName maps to legalName
    formData.legalName = props.item.extensions?.['sdo:legalName'] || '';
    // ceterms:ctid maps to ctid
    formData.ctid = props.item.extensions?.['ceterms:ctid'] || '';
    // ceterms:subjectWebpage maps to webpage
    formData.webpage = props.item.extensions?.['ceterms:subjectWebpage'] || '';
    // ceterms:jurisdiction maps to jurisdiction
    formData.jurisdiction = props.item.extensions?.['ceterms:jurisdiction'] || '';
  } else {
    // Reset for new
    formData.name = '';
    formData.description = '';
    formData.type = '';
    formData.logo = '';
    formData.legalName = '';
    formData.ctid = '';
    formData.webpage = '';
    formData.jurisdiction = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.name.trim()) {
    error.value = 'Name is required';
    return;
  }

  if (!formData.description.trim()) {
    error.value = 'Description is required';
    return;
  }

  saving.value = true;
  error.value = '';

  try {
    let savedItem;
    if (isEdit.value) {
      // Map form fields back to CASE structure
      savedItem = {
        ...props.item,
        name: formData.name,
        description: formData.description,
        type: formData.type,
        webpage: formData.webpage,
        logo: formData.logo,
        jurisdiction: formData.jurisdiction,
        legalName: formData.legalName,
        ctid: formData.ctid,
        extensions: {
          ...(props.item?.extensions || {}),
          'salt:type': 'organization'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        identifier: 'org_' + Date.now(),
        name: formData.name,
        description: formData.description,
        type: formData.type,
        webpage: formData.webpage,
        logo: formData.logo,
        jurisdiction: formData.jurisdiction,
        legalName: formData.legalName,
        ctid: formData.ctid,
        extensions: {
          'salt:type': 'organization'
        },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save organization: ' + e.message;
  } finally {
    saving.value = false;
  }
}

function closeModal() {
 emit('hidden');
}
</script>

<style scoped>
.modal-dialog {
  max-width: 95vw;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
