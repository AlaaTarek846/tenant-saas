<script setup>
import { computed } from 'vue';
import {
    actionLabel,
    categoryLabel,
    groupPermissions,
} from '@/utils/permissions';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'الصلاحيات',
    },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const permissionGroups = computed(() => groupPermissions(props.permissions));

const allPermissionNames = computed(() => props.permissions.map((permission) => permission.name));

const allSelected = computed({
    get: () => allPermissionNames.value.length > 0
        && allPermissionNames.value.every((name) => selected.value.includes(name)),
    set: (value) => {
        selected.value = value ? [...allPermissionNames.value] : [];
    },
});

function categoryPermissionNames(items) {
    return items.map((permission) => permission.name);
}

function isCategoryFullySelected(items) {
    const names = categoryPermissionNames(items);

    return names.length > 0 && names.every((name) => selected.value.includes(name));
}

function isCategoryPartiallySelected(items) {
    const names = categoryPermissionNames(items);
    const selectedCount = names.filter((name) => selected.value.includes(name)).length;

    return selectedCount > 0 && selectedCount < names.length;
}

function toggleCategory(items, checked) {
    const names = categoryPermissionNames(items);

    if (checked) {
        selected.value = [...new Set([...selected.value, ...names])];
        return;
    }

    selected.value = selected.value.filter((name) => !names.includes(name));
}
</script>

<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="form-label mb-0">{{ title }}</label>
            <label class="d-flex align-items-center gap-2 mb-0">
                <PrimeCheckbox
                    v-model="allSelected"
                    input-id="perm-select-all"
                    binary
                />
                <span>اختر الكل</span>
            </label>
        </div>

        <div
            v-for="(items, category) in permissionGroups"
            :key="category"
            class="border rounded p-3 mb-3"
        >
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">{{ categoryLabel(category) }}</h6>
                <label class="d-flex align-items-center gap-2 mb-0">
                    <PrimeCheckbox
                        :model-value="isCategoryFullySelected(items)"
                        :indeterminate="isCategoryPartiallySelected(items)"
                        :input-id="`perm-select-all-${category}`"
                        binary
                        @update:model-value="toggleCategory(items, $event)"
                    />
                    <span class="small">اختر الكل</span>
                </label>
            </div>
            <div class="row g-3">
                <div
                    v-for="permission in items"
                    :key="permission.id ?? permission.name"
                    class="col-md-6 col-lg-3"
                >
                    <label class="d-flex align-items-center gap-2 mb-0">
                        <PrimeCheckbox
                            v-model="selected"
                            :input-id="`perm-${permission.name}`"
                            name="permissions"
                            :value="permission.name"
                        />
                        <span>{{ actionLabel(permission.name) }}</span>
                    </label>
                </div>
            </div>
        </div>
        <p v-if="!permissions.length" class="text-muted mb-0">لا توجد صلاحيات متاحة.</p>
    </div>
</template>
