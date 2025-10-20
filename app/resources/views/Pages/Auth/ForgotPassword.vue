<script setup>
import Input from '@/Components/Form/Input.vue';
import Head from '@/Components/Head.vue';
import { Form } from '@inertiajs/vue3';

</script>

<template>
	<Head title="Forgot Password" />

	<h1>Forgot Password</h1>
	<p>Please enter your email address to reset your password.</p>
	<Form
		action="/forgot"
		method="post"
		#default="{ errors }"
	>
		<Input
			id="email"
			type="email"
			label="Email"
			placeholder="Enter your email"
			required
			:error="errors.email"
		/>
		<button type="submit" class="btn btn-primary">
			<i class="bi bi-envelope"></i>
			Send Reset Link
		</button>
	</Form>
</template>