<template>
    <div class="container-fluid py-4">
        <div class="row g-4">
            <!-- Sidebar: Structure Tree -->
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

                        <div v-else class="list-group list-group-flush">
                            <div v-for="page in structure.pages" :key="page.id" class="mb-3">
                                <div class="d-flex align-items-center p-2 rounded mb-1 transition-all" 
                                     :class="selectedItem?.id === page.id && selectedType === 'page' ? 'bg-primary text-white shadow-sm' : 'bg-light text-dark hover-lift'"
                                     @click="selectPage(page)"
                                     style="cursor: pointer;">
                                    <i class="bi bi-file-earmark-text me-2 opacity-75"></i>
                                    <span class="fw-semibold">{{ page.title }}</span>
                                </div>

                                <!-- Page Items -->
                                <div class="ps-2 border-start ms-3 border-2">
                                    <div v-for="(item, idx) in page.items" :key="item.id" 
                                         class="d-flex align-items-center p-2 mb-1 rounded transition-all group-item-actions"
                                         :class="selectedItem?.id === item.id && selectedType === 'item' ? 'bg-white border-start border-4 border-primary shadow-sm' : 'text-muted hover-bg'"
                                         @click.stop="selectItem(item)"
                                         style="cursor: pointer; font-size: 0.9rem;">
                                        <span class="badge bg-light text-secondary border me-2 rounded-pill" style="width: 35px;">{{ item.qid }}</span>
                                        <span class="text-truncate flex-grow-1">{{ item.question }}</span>
                                        
                                        <div class="btn-group btn-group-sm ms-2 opacity-0 group-item-actions-show" v-if="!structure.is_active">
                                            <button class="btn btn-xs btn-light py-0 px-1" 
                                                    @click.stop="moveItem(page, idx, -1)" 
                                                    :disabled="idx === 0"
                                                    title="Move Up">
                                                <i class="bi bi-chevron-up" style="font-size: 0.7em;"></i>
                                            </button>
                                            <button class="btn btn-xs btn-light py-0 px-1" 
                                                    @click.stop="moveItem(page, idx, 1)" 
                                                    :disabled="idx === page.items.length - 1"
                                                    title="Move Down">
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

            <!-- Main Canvas: Editor -->
            <div class="col-md-6">
                <div v-if="selectedItem && selectedType === 'item'">
                    <question-editor 
                        :item="selectedItem" 
                        :saving="saving"
                        @save="saveItem"
                        @cancel="selectedItem = null"
                    />
                </div>
                
                <div v-else-if="selectedItem && selectedType === 'page'" class="card border-0 shadow-sm">
                     <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold">Edit Page</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Page Title</label>
                            <input type="text" class="form-control form-control-lg" v-model="selectedItem.title" disabled>
                        </div>
                        <div class="alert alert-light border-0 bg-light text-secondary d-flex align-items-center">
                            <i class="bi bi-info-circle me-2 fs-5"></i>
                            Page editing and reordering coming soon.
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-5 text-muted d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 400px;">
                    <div class="bg-light rounded-circle p-4 mb-3">
                        <i class="bi bi-cursor text-secondary display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Ready to Edit</h5>
                    <p class="text-secondary">Select a page or question from the sidebar to begin.</p>
                </div>
            </div>

            <!-- Actions Panel -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="card-title text-secondary text-uppercase small fw-bold mb-3">Survey Status</h6>
                        <div class="d-flex align-items-center mb-4">
                            <span class="badge rounded-pill px-3 py-2 me-2" :class="structure.is_active ? 'bg-success' : 'bg-warning text-dark'">
                                <i class="bi me-1" :class="structure.is_active ? 'bi-check-circle-fill' : 'bi-cone-striped'"></i>
                                {{ structure.is_active ? 'LIVE' : 'DRAFT' }}
                            </span>
                            <small class="text-muted font-monospace">v{{ structure.version }}</small>
                        </div>
                        
                        <div v-if="structure.is_active">
                            <div class="alert alert-warning small mb-3 border-0 bg-warning bg-opacity-10 text-warning-emphasis">
                                <i class="bi bi-lock-fill me-1"></i>
                                Live version is locked.
                            </div>
                            <button class="btn btn-outline-dark w-100 shadow-sm" @click="createDraft">
                                <i class="bi bi-pencil-square me-2"></i> Create Draft to Edit
                            </button>
                        </div>
                        
                        <div v-else>
                            <button class="btn btn-success w-100 mb-2 shadow-sm text-white" @click="publishVersion">
                                <i class="bi bi-rocket-takeoff me-2"></i> Publish Version
                            </button>
                            <p class="small text-muted text-center mt-2 mb-0">
                                Make this version live and collect data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import QuestionEditor from './QuestionEditor.vue';
import LogicEditor from './LogicEditor.vue';

const props = defineProps({
    initialVersionId: { type: Number, required: true },
    surveyId: { type: Number, required: true }
});

const structure = ref({ pages: [] });
const loading = ref(false);
const saving = ref(false);
const selectedItem = ref(null);
const selectedType = ref(null); // 'page' or 'item'

const refreshStructure = async () => {
    loading.value = true;
    try {
        // Use prop or fallback to latest
        const id = structure.value.id || props.initialVersionId;
        const { data } = await axios.get(`/builder/structure/${id}`);
        structure.value = data;
    } catch (e) {
        console.error(e);
        alert('Failed to load structure');
    } finally {
        loading.value = false;
    }
};

const selectItem = (item) => {
    selectedItem.value = JSON.parse(JSON.stringify(item)); // Deep copy for editing
    selectedType.value = 'item';
};

const selectPage = (page) => {
    selectedItem.value = page;
    selectedType.value = 'page';
};

const saveItem = async (updatedItem) => {
    saving.value = true;
    try {
        await axios.post(`/builder/item/${updatedItem.id}`, updatedItem);
        await refreshStructure(); // Reload to see changes
        // Re-select logic omitted for brevity, user will re-click
        selectedItem.value = null; 
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Failed to save');
    } finally {
        saving.value = false;
    }
};

const createDraft = async () => {
    if (!confirm('Create a new editable draft from this version?')) return;
    try {
        const { data } = await axios.post(`/builder/draft/${props.surveyId}`);
        // Switch to new draft
        const { data: newStructure } = await axios.get(`/builder/structure/${data.draft_id}`);
        structure.value = newStructure;
        selectedItem.value = null;
    } catch (e) {
        console.error(e);
        alert('Failed to create draft');
    }
};

const publishVersion = async () => {
    if (!confirm('Are you sure? This will make this version LIVE and collect real data.')) return;
    try {
        await axios.post(`/builder/publish/${structure.value.id}`);
        refreshStructure();
    } catch (e) {
        console.error(e);
        alert('Failed to publish');
    }
};

const moveItem = async (page, index, direction) => {
    const items = [...page.items];
    const newIndex = index + direction;
    
    if (newIndex < 0 || newIndex >= items.length) return;
    
    // Swap items
    [items[index], items[newIndex]] = [items[newIndex], items[index]];
    
    // Update sort orders locally
    items.forEach((item, idx) => item.sort_order = idx);
    
    // Optimistic update
    page.items = items;
    
    try {
        await axios.post('/builder/reorder', {
            items: items.map(i => ({ id: i.id, sort_order: i.sort_order }))
        });
    } catch (e) {
        console.error(e);
        alert('Failed to reorder');
        refreshStructure(); // Revert on failure
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
