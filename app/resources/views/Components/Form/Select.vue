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
	options: {
		type: Object,
		required: false,
		default: () => ({}),
	},
	label: {
		type: String,
		required: false,
		default: null,
	},
	placeholder: {
		type: String,
		required: false,
		default: null,
	},
	modelValue: {
		type: [String, Number, Object],
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

const modelValue = ref(props.modelValue);
const emit = defineEmits(['update:modelValue']);
const $attrs = useAttrs();

// use dedicated event handler with manual updating of ref() to avoid breaking the disabled selected label
function onChange(event) {
	modelValue.value = event.target.value;
	emit('update:modelValue', event.target.value);
}
</script>

<template>
	<div :class="{'mb-3': !props.noMb, [props.outerClass]: props.outerClass}">
		<slot name="before" />
		<div class="form-floating">
			<select
				@change="onChange"
				:id="props.id"
				:name="props.name ?? props.id"
				class="form-select"
				:class="{
					'is-invalid': props.error,
				}"
				:value="modelValue"
				v-bind="$attrs"
			>
				<option v-if="props.placeholder" value="" disabled selected class="d-none">{{ props.placeholder }}</option>
				<option v-for="(value, key) in props.options" :key="key" :value="value">
					{{ value }}
				</option>
				<slot />
			</select>
			<label :for="props.id">{{ props.label }}</label>
		</div>
		<slot name="after" />
		<div v-if="props.error" class="invalid-feedback d-block">
			{{ props.error }}
		</div>
	</div>
</template>