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
		type: [Boolean, Array],
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

function isArrayMode() {
	return props.value !== null && Array.isArray(props.modelValue);
}

function isChecked() {
	if (isArrayMode()) {
		return props.modelValue.includes(props.value);
	}

	return !!props.modelValue;
}

function onChange(event) {
	if (isArrayMode()) {
		const next = [...props.modelValue];
		const currentIndex = next.indexOf(props.value);

		if (event.target.checked && currentIndex === -1) {
			next.push(props.value);
		}

		if (!event.target.checked && currentIndex !== -1) {
			next.splice(currentIndex, 1);
		}

		emit('update:modelValue', next);
		return;
	}

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
				:checked="isChecked()"
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