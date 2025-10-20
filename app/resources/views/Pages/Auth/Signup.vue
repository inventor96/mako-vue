<script setup>
import Input from '@/Components/Form/Input.vue';
import Head from '@/Components/Head.vue';
import { Form, Link } from '@inertiajs/vue3';

</script>

<template>
	<Head title="Sign Up" />

	<h1>Sign Up</h1>
	<Form
		action="/signup"
		method="post"
		#default="{ errors }"
	>
		<Input
			id="first_name"
			label="First Name"
			placeholder="First Name"
			required
			autocomplete="given-name"
			:error="errors.first_name"
		/>
		<Input
			id="last_name"
			label="Last Name"
			placeholder="Last Name"
			required
			autocomplete="family-name"
			:error="errors.last_name"
		/>
		<Input
			id="email"
			type="email"
			label="Email"
			placeholder="Email"
			required
			autocomplete="email"
			:error="errors.email"
		/>
		<Input
			id="password"
			type="password"
			label="Password"
			placeholder="Password"
			required
			autocomplete="new-password"
			:error="errors.password"
		/>
		<Input
			id="confirm_password"
			type="password"
			label="Confirm Password"
			placeholder="Confirm Password"
			required
			autocomplete="new-password"
			:error="errors.confirm_password"
		/>
		<button type="submit" class="btn btn-primary">
			<i class="bi bi-person-plus"></i>
			Sign Up
		</button>
	</Form>
	<p>Already have an account? <Link href="/login">Log in here.</Link></p>
</template>