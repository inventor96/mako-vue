<script setup>
import { onMounted, ref, onBeforeUnmount } from 'vue';
import { Alert } from 'bootstrap';

const props = defineProps({
	type: {
		type: String,
		required: true,
	},
	msgs: {
		type: [String, Array, Object],
		required: true,
	},
	timeout: {
		type: Number,
		required: false,
		default: null,
	},
});

const emit = defineEmits(['closed']);
const alert = ref(null);

let alertInstance = null;
let timeoutId = null;

function handleClosed() {
	emit('closed');
}

onMounted(() => {
	// if timeout not already set, default timeout to 10000 unless it's a danger or warning type
	const timeout = (props.timeout === null && (props.type !== 'danger' && props.type !== 'warning')) ? 10000 : props.timeout;

	alertInstance = Alert.getOrCreateInstance(alert.value);

	// listen for manual close
	alert.value.addEventListener('closed.bs.alert', handleClosed);

	// dismiss alert after timeout
	if (timeout > 0) {
		timeoutId = setTimeout(() => {
			alertInstance.close();
			// handleClosed will be called by the event listener
		}, timeout);
	}
});

onBeforeUnmount(() => {
	if (alert.value) {
		alert.value.removeEventListener('closed.bs.alert', handleClosed);
	}
	if (timeoutId) {
		clearTimeout(timeoutId);
	}
});
</script>

<template>
	<div :class="['alert', 'alert-' + props.type, 'fw-normal', 'alert-dismissible', 'fade', 'show']" ref="alert">
		<template v-if="(Array.isArray(props.msgs) && props.msgs.length > 1) || (typeof props.msgs === 'object' && Object.keys(props.msgs).length > 1)">
			<ul class="mb-0">
				<li v-for="(msg, index) in props.msgs" :key="index">{{ msg }}</li>
			</ul>
		</template>
		<template v-else>
			{{ Array.isArray(props.msgs) ? props.msgs[0] : (typeof props.msgs === 'object' ? Object.values(props.msgs)[0] : props.msgs) }}
		</template>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
</template>
