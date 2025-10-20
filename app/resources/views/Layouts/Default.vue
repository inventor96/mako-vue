<script setup>
import { /* Dropdown, */ Collapse } from 'bootstrap'
import NavLink from '@/Components/NavLink.vue';
import Alert from '@/Components/Alert.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
	_env: {
		type: String,
		default: 'unknown'
	},
	_left_navlinks: {
		type: Array,
		default: () => []
	},
	_right_navlinks: {
		type: Array,
		default: () => []
	},
	_container_error: {
		type: [Array, Object],
		default: () => []
	},
	_container_warning: {
		type: [Array, Object],
		default: () => []
	},
	_container_success: {
		type: [Array, Object],
		default: () => []
	}
});

const collapseRef = ref(null);
let collapse = null;

onMounted(() => {
	if (collapseRef.value) {
		collapse = new Collapse(collapseRef.value, { toggle: false });
	}
});

const page = usePage();
watch(
	() => page.url,
	() => {
		if (collapse) {
			collapse.hide();
		}
	}
);

const dbLink = computed(
	() => {
		if (props._env === 'docker') {
			return `//db.${window.location.host}:${window.location.port}`;
		}
		return null;
	}
);
const mailLink = computed(
	() => {
		if (props._env === 'docker') {
			return `//mail.${window.location.host}:${window.location.port}`;
		}
		return null;
	}
);
</script>

<template>
	<!-- environment alert -->
	<div v-if="props._env !== 'prod'" id="env_alert" class="alert alert-warning m-0 p-1 d-flex justify-content-around">
		<a v-if="dbLink" :href="dbLink" target="_blank" class="icon-link">
			Adminer
			<i class="bi bi-box-arrow-up-right"></i>
		</a>
		<span>{{props._env}} environment</span>
		<a v-if="mailLink" :href="mailLink" target="_blank" class="icon-link">
			Mailpit
			<i class="bi bi-box-arrow-up-right"></i>
		</a>
	</div>

	<!-- navigation bar -->
	<nav id="navbar" data-bs-theme="dark" class="navbar navbar-expand-lg bg-primary mb-3 shadow">
		<div class="container">
			<Link class="navbar-brand fw-semibold" href="/">Mako Vue</Link>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse fw-normal" id="navbarNav" ref="collapseRef">
				<ul class="navbar-nav">
					<NavLink
						v-for="link in props._left_navlinks"
						:key="link.path"
						:path="link.path"
						:name="link.name"
						:icon="link.icon ?? ''"
						:active="link.active"
						:dropdowns="link.dropdowns"
					/>
				</ul>
				<ul class="navbar-nav ms-auto">
					<NavLink
						v-for="link in props._right_navlinks"
						:key="link.path"
						:path="link.path"
						:name="link.name"
						:icon="link.icon ?? ''"
						:active="link.active"
						:dropdowns="link.dropdowns"
					/>
				</ul>
			</div>
		</div>
	</nav>

	<div id="container" class="container pb-5">
		<!-- page alerts -->
		<Alert
			v-for="(msg, index) in props._container_error"
			:key="index"
			:msgs="msg"
			type="danger"
			:timeout="0"
		/>
		<Alert
			v-for="(msg, index) in props._container_warning"
			:key="index"
			:msgs="msg"
			type="warning"
			:timeout="0"
		/>
		<Alert
			v-for="(msg, index) in props._container_success"
			:key="index"
			:msgs="msg"
			type="success"
		/>

		<!-- page content -->
		<slot />
	</div>
</template>
