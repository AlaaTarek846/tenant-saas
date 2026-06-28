<script setup>
import ErrorPage from '@/components/errors/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="500"
        title="خطأ في الخادم"
        description="حدث خطأ من جانبنا. حاول مرة أخرى لاحقاً."
        primary-label="رجوع"
        primary-action="back"
    />
</template>
