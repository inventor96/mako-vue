<script setup>
import Input from '@/Components/Form/Input.vue';
import Head from '@/Components/Head.vue';
import { Form } from '@inertiajs/vue3';

const props = defineProps({
	token: String
});
</script>

<template>
	<Head title="Reset Password" />

	<h1>Reset Password</h1>
	<p>Please enter your new password.</p>
	<Form
		:action="'/reset/' + props.token"
		method="post"
		#default="{ errors }"
	>
		<Input
			id="password"
			type="password"
			label="New Password"
			placeholder="New Password"
			required
			:error="errors.password"
		/>
		<Input
			id="confirm_password"
			type="password"
			label="Confirm Password"
			placeholder="Confirm Password"
			required
			:error="errors.confirm_password"
		/>
		<button type="submit" class="btn btn-primary">
			<i class="bi bi-lock"></i>
			Reset Password
		</button>
	</Form>
</template>