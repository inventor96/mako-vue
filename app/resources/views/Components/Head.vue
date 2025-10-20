<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
	title: {
		type: String,
		default: null
	}
});

const pageTitle = computed(() => {
	return props.title ? `${props.title} | Mako Vue` : 'Mako Vue';
});
</script>

<template>
	<Head :title="pageTitle" />
</template>