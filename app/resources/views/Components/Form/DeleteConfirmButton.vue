<script setup>
import Modal from '@/Components/Modal.vue';

const props = defineProps({
	id: {
		type: String,
		required: true,
	},
	buttonText: {
		type: String,
		required: false,
		default: 'Delete',
	},
	buttonClass: {
		type: String,
		required: false,
		default: 'btn-danger',
	},
	itemText: {
		type: String,
		required: false,
		default: 'this item',
	},
	form: {
		type: String,
		required: false,
	},
	processing: {
		type: Boolean,
		required: false,
		default: false,
	},
});
</script>

<template>
	<Modal
		:id="`delete-modal-${props.id}`"
		title="Confirm Deletion"
		confirmText=""
		closeText="Cancel"
	>
		<p v-if="props.itemText">Are you sure you want to delete {{ props.itemText }}?</p>
		<slot />

		<template #footer>
			<button type="submit" class="btn btn-danger" :disabled="props.processing" :form="props.form">
				<span class="spinner-border spinner-border-sm" v-if="props.processing" role="status" aria-hidden="true"></span>
				<i class="bi bi-trash3"></i>
				{{ props.buttonText }}
			</button>
		</template>
	</Modal>
	<button type="button" class="btn" :class="props.buttonClass" data-bs-toggle="modal" :data-bs-target="`#delete-modal-${props.id}`">
		<i class="bi bi-trash3"></i>
		<span class="d-none d-md-inline-block ms-1">{{ props.buttonText }}</span>
	</button>
</template>