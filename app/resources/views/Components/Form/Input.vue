<script setup>
import { useAttrs, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
	id: {
		type: String,
		required: true,
	},
	name: {
		type: String,
		required: false,
		default: null,
	},
	type: {
		type: String,
		required: false,
		default: 'text',
	},
	label: {
		type: String,
		required: true,
	},
	modelValue: {
		type: [String, Number],
		required: false,
		default: '',
	},
	error: {
		type: String,
		required: false,
		default: null,
	},
	noMb: {
		type: Boolean,
		required: false,
		default: false,
	},
	outerClass: {
		type: String,
		required: false,
		default: null,
	},
});

const emit = defineEmits(['update:modelValue']);

const $attrs = useAttrs();
const inputRef = ref(null);
defineExpose({ inputRef });

function onInput(event) {
	emit('update:modelValue', event.target.value);
}
</script>

<template>
	<div :class="{'mb-3': !props.noMb, [props.outerClass]: props.outerClass}">
		<slot name="before" />
		<div class="form-floating">
			<input
				ref="inputRef"
				:id="props.id"
				:name="props.name ?? props.id"
				:type="props.type"
				class="form-control"
				:class="{'is-invalid': props.error}"
				:placeholder="props.label"
				:value="props.modelValue"
				@input="onInput"
				v-bind="$attrs"
			/>
			<label :for="props.id">{{ props.label }}</label>
		</div>
		<slot name="after" />
		<div v-if="props.error" class="invalid-feedback d-block">
			{{ props.error }}
		</div>
	</div>
</template>