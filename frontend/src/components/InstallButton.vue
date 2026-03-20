<template>
	<button class="apb-install" @click="$emit('install')">
		Install &amp; Activate Plugin
	</button>
</template>

<script setup lang="ts">
defineEmits<{ install: [] }>();
</script>

<style scoped>
.apb-install {
	margin-top: 16px;
	padding: 12px 24px;
	font-size: 15px;
	font-weight: 600;
	color: #ffffff;
	background: #10b981;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.2s ease;
	box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2), 0 2px 4px -1px rgba(16, 185, 129, 0.1);
	display: inline-flex;
	align-items: center;
	justify-content: center;
}

.apb-install:hover {
	background: #059669;
	transform: translateY(-1px);
	box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.3), 0 4px 6px -1px rgba(16, 185, 129, 0.2);
}

.apb-install:active {
	transform: translateY(0);
	box-shadow: 0 2px 4px -1px rgba(16, 185, 129, 0.2);
}
</style>
