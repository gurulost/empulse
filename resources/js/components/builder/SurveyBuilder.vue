<template>
    <div class="container-fluid py-4">
        <div v-if="!hasStructureSource" class="card border-0 shadow-sm">
            <div class="card-body py-5 text-center">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-ui-checks-grid text-secondary fs-2"></i>
                </div>
                <h4 class="mb-2">No survey version is loaded yet</h4>
                <p class="text-muted mb-0">
                    Import and publish a survey version first. Once a version exists, the builder will show the full page, section, and item structure here.
                </p>
            </div>
        </div>

        <div v-else class="row g-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-secondary text-uppercase small ls-1">Structure</h6>
                        <button class="btn btn-sm btn-light text-primary rounded-circle" @click="refreshStructure" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    <div class="card-body p-2 overflow-auto" style="max-height: calc(100vh - 200px);">
                        <div v-if="loading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>

                        <div v-else-if="errorMessage" class="alert alert-danger m-2 mb-0">
                            {{ errorMessage }}
                        </div>

                        <div v-else-if="!structure.pages.length" class="text-center text-muted py-4 px-3">
                            No pages are available in this version yet.
                        </div>

                        <div v-else class="list-group list-group-flush">
                            <div v-for="page in structure.pages" :key="page.id" class="mb-3">
                                <div
                                    class="d-flex align-items-center p-2 rounded mb-1 transition-all"
                                    :class="isSelected('page', page.id) ? 'bg-primary text-white shadow-sm' : 'bg-light text-dark hover-lift'"
                                    @click="selectPage(page)"
                                    style="cursor: pointer;"
                                >
                                    <i class="bi bi-file-earmark-text me-2 opacity-75"></i>
                                    <span class="fw-semibold text-truncate">{{ page.title || 'Untitled Page' }}</span>
                                </div>

                                <div class="ps-2 border-start ms-3 border-2">
                                    <div
                                        v-for="(item, idx) in page.items"
                                        :key="item.id"
                                        class="d-flex align-items-center p-2 mb-1 rounded transition-all group-item-actions"
                                        :class="isSelected('item', item.id) ? 'bg-white border-start border-4 border-primary shadow-sm' : 'text-muted hover-bg'"
                                        @click.stop="selectItem(item)"
                                        style="cursor: pointer; font-size: 0.9rem;"
                                    >
                                        <span class="badge bg-light text-secondary border me-2 rounded-pill">{{ item.qid }}</span>
                                        <span class="text-truncate flex-grow-1">{{ item.question }}</span>
                                        <div class="btn-group btn-group-sm ms-2 opacity-0 group-item-actions-show" v-if="!structure.is_active">
                                            <button
                                                class="btn btn-xs btn-light py-0 px-1"
                                                @click.stop="moveItem(page.items, idx, -1)"
                                                :disabled="idx === 0"
                                                title="Move Up"
                                            >
                                                <i class="bi bi-chevron-up" style="font-size: 0.7em;"></i>
                                            </button>
                                            <button
                                                class="btn btn-xs btn-light py-0 px-1"
                                                @click.stop="moveItem(page.items, idx, 1)"
                                                :disabled="idx === page.items.length - 1"
                                                title="Move Down"
                                            >
                                                <i class="bi bi-chevron-down" style="font-size: 0.7em;"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-for="section in page.sections" :key="section.id" class="mb-2">
                                        <div
                                            class="d-flex align-items-center p-2 rounded mb-1 transition-all"
                                            :class="isSelected('section', section.id) ? 'bg-info text-dark shadow-sm' : 'bg-white text-dark border hover-bg'"
                                            @click.stop="selectSection(section)"
                                            style="cursor: pointer;"
                                        >
                                            <i class="bi bi-layout-text-sidebar me-2 opacity-75"></i>
                                            <span class="fw-semibold text-truncate">{{ section.title || 'Untitled Section' }}</span>
                                        </div>
                                        <div class="ps-3 border-start ms-2">
                                            <div
                                                v-for="(item, idx) in section.items"
                                                :key="item.id"
                                                class="d-flex align-items-center p-2 mb-1 rounded transition-all group-item-actions"
                                                :class="isSelected('item', item.id) ? 'bg-white border-start border-4 border-primary shadow-sm' : 'text-muted hover-bg'"
                                                @click.stop="selectItem(item)"
                                                style="cursor: pointer; font-size: 0.9rem;"
                                            >
                                                <span class="badge bg-light text-secondary border me-2 rounded-pill">{{ item.qid }}</span>
                                                <span class="text-truncate flex-grow-1">{{ item.question }}</span>
                                                <div class="btn-group btn-group-sm ms-2 opacity-0 group-item-actions-show" v-if="!structure.is_active">
                                                    <button
                                                        class="btn btn-xs btn-light py-0 px-1"
                                                        @click.stop="moveItem(section.items, idx, -1)"
                                                        :disabled="idx === 0"
                                                        title="Move Up"
                                                    >
                                                        <i class="bi bi-chevron-up" style="font-size: 0.7em;"></i>
                                                    </button>
                                                    <button
                                                        class="btn btn-xs btn-light py-0 px-1"
                                                        @click.stop="moveItem(section.items, idx, 1)"
                                                        :disabled="idx === section.items.length - 1"
                                                        title="Move Down"
                                                    >
                                                        <i class="bi bi-chevron-down" style="font-size: 0.7em;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div v-if="selectedItem && selectedType === 'item'">
                    <question-editor
                        :key="selectedItem.id"
                        :item="selectedItem"
                        :saving="saving"
                        :read-only="structure.is_active"
                        @save="saveItem"
                        @cancel="clearSelection"
                    />
                </div>

                <div v-else-if="selectedItem && selectedType === 'page'" class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold">Edit Page</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Page Title</label>
                            <input type="text" class="form-control form-control-lg" v-model="selectedItem.title" :disabled="structure.is_active">
                        </div>
                        <div v-if="structure.is_active" class="alert alert-light border-0 bg-light text-secondary">
                            Create a draft to edit this page title.
                        </div>
                        <div v-else class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <button class="btn btn-light px-4" @click="clearSelection">Cancel</button>
                            <button class="btn btn-primary px-4 shadow-sm" @click="savePage" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ saving ? 'Saving...' : 'Save Page' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="selectedItem && selectedType === 'section'" class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold">Edit Section</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Section Title</label>
                            <input type="text" class="form-control form-control-lg" v-model="selectedItem.title" :disabled="structure.is_active">
                        </div>
                        <div v-if="structure.is_active" class="alert alert-light border-0 bg-light text-secondary">
                            Create a draft to edit this section title.
                        </div>
                        <div v-else class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <button class="btn btn-light px-4" @click="clearSelection">Cancel</button>
                            <button class="btn btn-primary px-4 shadow-sm" @click="saveSection" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ saving ? 'Saving...' : 'Save Section' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-5 text-muted d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 400px;">
                    <div class="bg-light rounded-circle p-4 mb-3">
                        <i class="bi bi-cursor text-secondary display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Ready to Edit</h5>
                    <p class="text-secondary mb-0">Select a page, section, or question from the sidebar to begin.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="card-title text-secondary text-uppercase small fw-bold mb-3">Survey Status</h6>
                        <div v-if="structure.id" class="d-flex align-items-center mb-4">
                            <span class="badge rounded-pill px-3 py-2 me-2" :class="structure.is_active ? 'bg-success' : 'bg-warning text-dark'">
                                <i class="bi me-1" :class="structure.is_active ? 'bi-check-circle-fill' : 'bi-cone-striped'"></i>
                                {{ structure.is_active ? 'LIVE' : 'DRAFT' }}
                            </span>
                            <small class="text-muted font-monospace">v{{ structure.version }}</small>
                        </div>

                        <div v-if="!structure.id" class="alert alert-light border-0 bg-light text-secondary mb-0">
                            No survey structure is available yet.
                        </div>

                        <template v-else-if="structure.is_active">
                            <div class="alert alert-warning small mb-3 border-0 bg-warning bg-opacity-10 text-warning-emphasis">
                                <i class="bi bi-lock-fill me-1"></i>
                                Live versions are locked from direct editing.
                            </div>
                            <button class="btn btn-outline-dark w-100 shadow-sm" @click="createDraft" :disabled="!surveyId">
                                <i class="bi bi-pencil-square me-2"></i> Create Draft to Edit
                            </button>
                        </template>

                        <template v-else-if="structure.id">
                            <button class="btn btn-success w-100 mb-2 shadow-sm text-white" @click="publishVersion">
                                <i class="bi bi-rocket-takeoff me-2"></i> Publish Version
                            </button>
                            <p class="small text-muted text-center mt-2 mb-0">
                                Make this version live and collect data.
                            </p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import QuestionEditor from './QuestionEditor.vue';

const props = defineProps({
    initialVersionId: { type: Number, default: null },
    surveyId: { type: Number, default: null },
});

const clone = (value) => JSON.parse(JSON.stringify(value));

const structure = ref({ pages: [] });
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref(null);
const selectedItem = ref(null);
const selectedType = ref(null);

const hasStructureSource = computed(() => Boolean(structure.value.id || props.initialVersionId));
const surveyId = computed(() => props.surveyId);

const clearSelection = () => {
    selectedItem.value = null;
    selectedType.value = null;
};

const refreshStructure = async () => {
    const id = structure.value.id || props.initialVersionId;
    if (!id) {
        structure.value = { pages: [] };
        errorMessage.value = null;
        clearSelection();
        return;
    }

    loading.value = true;
    errorMessage.value = null;

    try {
        const { data } = await axios.get(`/admin/builder/structure/${id}`);
        structure.value = data;
    } catch (error) {
        console.error(error);
        errorMessage.value = error.response?.data?.message || 'Failed to load survey structure.';
    } finally {
        loading.value = false;
    }
};

const isSelected = (type, id) => selectedType.value === type && selectedItem.value?.id === id;

const selectPage = (page) => {
    selectedItem.value = clone(page);
    selectedType.value = 'page';
};

const selectSection = (section) => {
    selectedItem.value = clone(section);
    selectedType.value = 'section';
};

const selectItem = (item) => {
    selectedItem.value = clone(item);
    selectedType.value = 'item';
};

const saveItem = async (updatedItem) => {
    saving.value = true;
    try {
        await axios.post(`/admin/builder/item/${updatedItem.id}`, updatedItem);
        await refreshStructure();
        clearSelection();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to save item.');
    } finally {
        saving.value = false;
    }
};

const savePage = async () => {
    if (!selectedItem.value) {
        return;
    }

    saving.value = true;
    try {
        await axios.post(`/admin/builder/page/${selectedItem.value.id}`, { title: selectedItem.value.title });
        await refreshStructure();
        clearSelection();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to save page.');
    } finally {
        saving.value = false;
    }
};

const saveSection = async () => {
    if (!selectedItem.value) {
        return;
    }

    saving.value = true;
    try {
        await axios.post(`/admin/builder/section/${selectedItem.value.id}`, { title: selectedItem.value.title });
        await refreshStructure();
        clearSelection();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to save section.');
    } finally {
        saving.value = false;
    }
};

const createDraft = async () => {
    if (!surveyId.value) {
        alert('No survey is available to clone yet.');
        return;
    }

    if (!confirm('Create a new editable draft from this version?')) {
        return;
    }

    try {
        const { data } = await axios.post(`/admin/builder/draft/${surveyId.value}`);
        const { data: newStructure } = await axios.get(`/admin/builder/structure/${data.draft_id}`);
        structure.value = newStructure;
        clearSelection();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to create draft.');
    }
};

const publishVersion = async () => {
    if (!structure.value.id) {
        return;
    }

    if (!confirm('Are you sure? This version will become live for future survey waves.')) {
        return;
    }

    try {
        await axios.post(`/admin/builder/publish/${structure.value.id}`);
        await refreshStructure();
        clearSelection();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to publish version.');
    }
};

const moveItem = async (containerItems, index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= containerItems.length) {
        return;
    }

    const items = [...containerItems];
    [items[index], items[newIndex]] = [items[newIndex], items[index]];
    items.forEach((item, idx) => {
        item.sort_order = idx;
    });

    containerItems.splice(0, containerItems.length, ...items);

    try {
        await axios.post('/admin/builder/reorder', {
            items: items.map((item) => ({
                id: item.id,
                sort_order: item.sort_order,
            })),
        });
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Failed to reorder items.');
        await refreshStructure();
    }
};

onMounted(() => {
    refreshStructure();
});
</script>

<style scoped>
.hover-bg:hover {
    background-color: #f8f9fa;
}

.hover-lift:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.transition-all {
    transition: all 0.2s ease;
}

.group-item-actions .group-item-actions-show {
    opacity: 0;
    transition: opacity 0.2s;
}

.group-item-actions:hover .group-item-actions-show {
    opacity: 1;
}

.ls-1 {
    letter-spacing: 1px;
}
</style>
