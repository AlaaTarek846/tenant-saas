<script setup>
import ErrorPage from '@/components/errors/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="403"
        middle-icon="bx-lock-alt"
        title="غير مسموح"
        description="ليس لديك صلاحية للوصول إلى هذه الصفحة."
        primary-label="العودة للوحة التحكم"
        primary-action="home"
    />
</template>
