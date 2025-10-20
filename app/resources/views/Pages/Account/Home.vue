<script setup>
import Input from '@/Components/Form/Input.vue';
import Head from '@/Components/Head.vue';
import { Form } from '@inertiajs/vue3';


const props = defineProps({
	user: {
		type: Object,
		required: true,
	},
	delete_confirm: {
		type: Boolean,
		required: false,
		default: false,
	},
});
</script>

<template>
	<Head title="Account" />

	<h1>Account</h1>
	<Form
		:action="`/account`"
		method="put"
		#default="{ errors }"
	>
		<Input
			id="first_name"
			label="First Name"
			v-model="props.user.first_name"
			:error="errors.first_name"
		/>
		<Input
			id="last_name"
			label="Last Name"
			v-model="props.user.last_name"
			:error="errors.last_name"
		/>
		<Input
			id="email"
			label="Email"
			:model-value="props.user.email"
			disabled
			readonly
		>
			<template #after>
				<div class="form-text">Changing your account email address is not supported at this time.</div>
			</template>
		</Input>
		<button type="submit" class="btn btn-primary">
			<i class="bi bi-check-circle"></i>
			Save
		</button>
	</Form>

	<hr class="mt-5">
	<h2>Change Password</h2>
	<Form
		:action="`/account/password`"
		method="post"
		#default="{ errors }"
		resetOnSuccess
	>
		<input type="hidden" name="username" :value="props.user.email">
		<Input
			id="current_password"
			label="Current Password"
			type="password"
			autocomplete="current-password"
			:error="errors.current_password"
		/>
		<Input
			id="new_password"
			label="New Password"
			type="password"
			autocomplete="new-password"
			:error="errors.new_password"
		/>
		<Input
			id="new_password_confirmation"
			label="Confirm New Password"
			type="password"
			autocomplete="new-password"
			:error="errors.new_password_confirmation"
		/>
		<button type="submit" class="btn btn-primary">
			<i class="bi bi-check-circle"></i>
			Save
		</button>
	</Form>

	<hr class="mt-5">
	<h2>Delete Account</h2>
	<Form
		:action="`/account`"
		method="delete"
		#default="{ errors }"
	>
		<p>Are you sure you want to delete your account? This action cannot be undone.</p>
		<input type="hidden" name="delete_confirm" :value="props.delete_confirm">
		<button type="submit" class="btn btn-danger">
			<i class="bi bi-trash3"></i>
			Delete Account
		</button>
	</Form>
</template>