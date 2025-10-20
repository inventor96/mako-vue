<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
	path: {
		type: String,
		required: true
	},
	name: {
		type: String,
		required: true
	},
	icon: {
		type: String,
		required: false,
		default: '',
	},
	active: {
		type: Boolean,
		default: false
	},
	dropdowns: {
		type: Array,
		default: () => []
	},
});
</script>

<template>
	<li class="nav-item" :class="{ 'dropdown': props.dropdowns.length > 0 }">
		<Link
			v-if="!props.dropdowns.length"
			class="nav-link"
			:href="props.path"
			:class="{ active: props.active }"
		>
			<i v-if="props.icon" class="bi" :class="props.icon"></i>
			{{ props.name }}
		</Link>
		<a
			v-else
			class="nav-link dropdown-toggle"
			:class="{ active: props.active }"
			role="button"
			data-bs-toggle="dropdown"
			aria-expanded="false"
		>
			<i v-if="props.icon" class="bi" :class="props.icon"></i>
			{{ props.name }}
		</a>
		<ul v-if="props.dropdowns.length" class="dropdown-menu">
			<li v-for="dropdown in props.dropdowns" :key="dropdown.path">
				<Link
					class="dropdown-item"
					:href="dropdown.path"
					:class="{ active: dropdown.active }"
				>
					<i v-if="dropdown.icon" class="bi" :class="dropdown.icon"></i>
					{{ dropdown.name }}
				</Link>
			</li>
		</ul>
	</li>
</template>