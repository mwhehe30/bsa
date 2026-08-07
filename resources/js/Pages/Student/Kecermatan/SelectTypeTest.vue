<template>
    <div>
        <h1>SELECT TYPE PAGE</h1>
        <p>Exam ID: {{ exam?.id }}</p>
        <p>Exam Title: {{ exam?.title }}</p>
        <p>Duration: {{ exam?.duration }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    exam: Object
})

console.log('SelectType loaded', props.exam)
</script>
