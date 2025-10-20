<script setup>
import { Modal } from 'bootstrap';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
	id: {
		type: String,
		required: true,
	},
	title: {
		type: String,
		required: false,
		default: '',
	},
	confirmText: {
		type: String,
		required: false,
		default: 'Confirm',
	},
	closeText: {
		type: String,
		required: false,
		default: 'Close',
	},
	options: {
		type: Object,
		required: false,
		default: () => ({}),
	},
});

const emit = defineEmits([
	'shown',
	'closed',
]);

const modalElement = ref(null);
let modalInstance = null;

function handleShown() {
	emit('shown');
}

function handleClosed() {
	emit('closed');
}

function show() {
	if (modalInstance) {
		modalInstance.show();
	}
}

function hide() {
	if (modalInstance) {
		modalInstance.hide();
	}
}

function toggle() {
	if (modalInstance) {
		modalInstance.toggle();
	}
}

onMounted(() => {
	if (modalElement.value) {
		modalInstance = new Modal(modalElement.value, props.options);

		// pass up events
		modalElement.value.addEventListener('hidden.bs.modal', handleClosed);
		modalElement.value.addEventListener('shown.bs.modal', handleShown);
	}
});

function unmount() {
	if (modalInstance) {
		modalInstance.dispose();
		modalInstance = null;
	}
	if (modalElement.value) {
		modalElement.value.removeEventListener('hidden.bs.modal', unmount);
		modalElement.value.removeEventListener('hidden.bs.modal', handleClosed);
		modalElement.value.removeEventListener('shown.bs.modal', handleShown);
	}
}

onBeforeUnmount(() => {
	if (modalInstance) {
		// add cleanup logic for when the modal has been hidden
		modalElement.value.addEventListener('hidden.bs.modal', unmount);

		// hide the modal
		modalInstance.hide();
	}
});

// expose methods to parent
defineExpose({
	show,
	hide,
	toggle,
});
</script>

<template>
	<div :id="props.id" ref="modalElement" class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<slot name="header" />
					<h5 v-if="props.title" class="modal-title">{{ props.title }}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="props.closeText || 'Close'"></button>
				</div>
				<div class="modal-body">
					<slot />
				</div>
				<div class="modal-footer">
					<button v-if="props.closeText" type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ props.closeText }}</button>
					<button v-if="props.confirmText" type="button" class="btn btn-primary">{{ props.confirmText }}</button>
					<slot name="footer" />
				</div>
			</div>
		</div>
	</div>
</template>