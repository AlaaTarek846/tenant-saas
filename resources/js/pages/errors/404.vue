<script setup>
import ErrorPage from '@/components/errors/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="404"
        title="الصفحة غير موجودة"
        description="الصفحة التي تبحث عنها غير موجودة أو تم نقلها."
        primary-label="العودة للوحة التحكم"
        primary-action="home"
    />
</template>
