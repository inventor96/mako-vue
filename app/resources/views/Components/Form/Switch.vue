<script setup>
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
	label: {
		type: String,
		required: false,
		default: '',
	},
	modelValue: { // for true/false setups
		type: Boolean,
		required: false,
		default: false,
	},
	value: { // for array element setups
		type: [String, Number],
		required: false,
		default: null,
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
	innerClass: {
		type: String,
		required: false,
		default: '',
	},
});

const emit = defineEmits(['update:modelValue']);

function onChange(event) {
	emit('update:modelValue', event.target.checked);
}
</script>

<template>
	<div :class="{'mb-3': !props.noMb}">
		<div class="form-check form-switch" :class="props.innerClass">
			<input
				v-if="props.value === null"
				:id="props.id + '_hidden'"
				:name="props.name ?? props.id"
				type="hidden"
				value="0"
			/>
			<input
				:id="props.id"
				:name="props.name ?? props.id"
				type="checkbox"
				:value="props.value ?? 1"
				class="form-check-input"
				:class="{'is-invalid': props.error}"
				:checked="props.modelValue"
				@change="onChange"
			/>
			<slot name="label" />
			<label v-if="props.label" :for="props.id" class="form-check-label">{{ props.label }}</label>
		</div>
		<div v-if="props.error" class="invalid-feedback d-block">
			{{ props.error }}
		</div>
	</div>
</template>