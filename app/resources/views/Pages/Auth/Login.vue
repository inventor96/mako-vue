<script setup>
import Input from '@/Components/Form/Input.vue';
import Switch from '@/Components/Form/Switch.vue';
import Head from '@/Components/Head.vue';
import { Form, Link } from '@inertiajs/vue3';

</script>

<template>
	<Head title="Login" />

	<h1>Login</h1>
	<Form
		action="/login"
		method="post"
		#default="{ errors }"
	>
		<Input id="email" type="email" label="Email" placeholder="Email" required :error="errors.email" />
		<Input id="password" type="password" label="Password" placeholder="Password" required :error="errors.password" />
		<Switch id="remember" label="Remember Me" :model-value="true" :error="errors.remember" />
		<button class="btn btn-primary" type="submit">
			<i class="bi bi-box-arrow-in-right"></i>
			Login
		</button>
	</Form>
	<p>Don't have an account? <Link href="/signup">Let's get you signed up!</Link></p>
	<p>Forgot your password? <Link href="/forgot">Reset it here.</Link></p>
</template>