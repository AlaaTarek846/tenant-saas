<script setup>
import ErrorPage from '@/components/errors/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="503"
        middle-icon="bx-loader-circle bx-spin"
        title="الخدمة غير متاحة"
        description="الموقع تحت الصيانة أو الخادم مشغول حالياً. حاول بعد قليل."
        primary-label="إعادة المحاولة"
        primary-action="home"
        image="images/coming-soon.svg"
    />
</template>
