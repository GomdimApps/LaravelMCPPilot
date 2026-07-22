<script setup lang="ts">
defineProps<{
  title: string
  amount: number
}>()

defineEmits<{
  saved: [id: number]
}>()

const form = useForm({
  title: '',
  amount: 0,
})
</script>

<template>
  <form></form>
</template>
