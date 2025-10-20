<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
	pagination: Object,
	noCenter: {
		type: Boolean,
		required: false,
		default: false,
	},
	maxSidePages: {
		type: Number,
		required: false,
		default: 3,
	},
});

const firstRecordNumber = computed(() => {
	if (props.pagination.items === 0) {
		return 0;
	}
	return (props.pagination.current_page - 1) * props.pagination.items_per_page + 1;
});

const lastRecordNumber = computed(() => {
	if (props.pagination.items === 0) {
		return 0;
	}
	return Math.min(props.pagination.current_page * props.pagination.items_per_page, props.pagination.items);
});

// Compute page numbers to show
const pageNumbers = computed(() => {
	const total = props.pagination.number_of_pages;
	const current = props.pagination.current_page;
	const side = props.maxSidePages;
	const start = Math.max(current - side, 1);
	const end = Math.min(current + side, total);
	const pages = [];
	for (let i = start; i <= end; i++) {
		pages.push(i);
	}
	return pages;
});
</script>

<template>
	<div :class="noCenter ? '' : 'd-flex flex-column align-items-center'">
		<p>Showing {{ firstRecordNumber }} - {{ lastRecordNumber }} of {{ props.pagination.items }}</p>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item">
					<Link class="page-link" href="?page=1" :class="{ disabled: !props.pagination.previous }" aria-label="First">
						<span aria-hidden="true"><i class="bi lh-base bi-chevron-double-left"></i></span>
					</Link>
				</li>
				<li class="page-item">
					<Link class="page-link" :href="props.pagination.previous ?? '#'" :class="{ disabled: !props.pagination.previous }" aria-label="Previous">
						<span aria-hidden="true"><i class="bi lh-base bi-chevron-left"></i></span>
					</Link>
				</li>
				<li
					class="page-item"
					:class="{ active: page === props.pagination.current_page }"
					v-for="page in pageNumbers"
					:key="page"
				>
					<Link class="page-link" :href="`?page=${page}`">
						{{ page }}
					</Link>
				</li>
				<li class="page-item">
					<Link class="page-link" :href="props.pagination.next ?? '#'" :class="{ disabled: !props.pagination.next }" aria-label="Next">
						<span aria-hidden="true"><i class="bi lh-base bi-chevron-right"></i></span>
					</Link>
				</li>
				<li class="page-item">
					<Link class="page-link" :href="`?page=${props.pagination.number_of_pages}`" :class="{ disabled: !props.pagination.next }" aria-label="Last">
						<span aria-hidden="true"><i class="bi lh-base bi-chevron-double-right"></i></span>
					</Link>
				</li>
			</ul>
		</nav>
	</div>
</template>