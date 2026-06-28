<script setup>
import { computed } from 'vue';
import { getInitialsColor, getNameInitials } from '@/utils/initials';

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
    imageUrl: {
        type: String,
        default: null,
    },
    size: {
        type: [Number, String],
        default: 96,
    },
    shape: {
        type: String,
        default: 'circle',
    },
    fontSize: {
        type: String,
        default: null,
    },
});

const hasImage = computed(() => Boolean(props.imageUrl));
const initials = computed(() => getNameInitials(props.name));
const colors = computed(() => getInitialsColor(props.name));

const shapeClass = computed(() => (props.shape === 'circle' ? 'rounded-circle' : 'rounded'));

const styleVars = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    fontSize: props.fontSize ?? (Number(props.size) >= 80 ? '1.35rem' : '0.85rem'),
    backgroundColor: colors.value.bg,
    color: colors.value.color,
}));
</script>

<template>
    <img
        v-if="hasImage"
        :src="imageUrl"
        alt=""
        class="initials-avatar-image object-fit-cover border"
        :class="shapeClass"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
    <div
        v-else
        class="initials-avatar-fallback border d-flex align-items-center justify-content-center fw-semibold"
        :class="shapeClass"
        :style="styleVars"
        :title="name"
    >
        {{ initials }}
    </div>
</template>

<style scoped>
.object-fit-cover {
    object-fit: cover;
}

.initials-avatar-fallback {
    line-height: 1;
    user-select: none;
}
</style>
