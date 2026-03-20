<template>
	<div class="apb-plan">
		<div class="apb-plan__header">
			<h3>{{ plan.plugin_name }}</h3>
			<span class="apb-plan__badge">{{ plan.complexity }}</span>
		</div>

		<p class="apb-plan__desc">{{ plan.description }}</p>

		<!-- Files -->
		<div class="apb-plan__section">
			<h4>Files ({{ plan.files.length }})</h4>
			<ul>
				<li v-for="file in plan.files" :key="file.path">
					<code>{{ file.path }}</code>
					<span v-if="file.is_main" class="apb-plan__main">main</span>
					&mdash; {{ file.description }}
				</li>
			</ul>
		</div>

		<!-- Hooks -->
		<div v-if="plan.hooks_used.length" class="apb-plan__section">
			<h4>WordPress Hooks</h4>
			<div class="apb-plan__tags">
				<code v-for="hook in plan.hooks_used" :key="hook">{{ hook }}</code>
			</div>
		</div>

		<!-- APIs -->
		<div v-if="plan.wp_apis_used.length" class="apb-plan__section">
			<h4>WP APIs Used</h4>
			<div class="apb-plan__tags">
				<code v-for="api in plan.wp_apis_used" :key="api">{{ api }}</code>
			</div>
		</div>

		<!-- Security notes -->
		<div v-if="plan.security_notes.length" class="apb-plan__section">
			<h4>Security Notes</h4>
			<ul>
				<li v-for="(note, i) in plan.security_notes" :key="i">{{ note }}</li>
			</ul>
		</div>
	</div>
</template>

<script setup lang="ts">
import type { PluginPlan } from '../types';

defineProps<{ plan: PluginPlan }>();
</script>

<style scoped>
.apb-plan {
	width: 100%;
	max-width: 650px;
	background: #ffffff;
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	padding: 20px;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.apb-plan__header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.apb-plan__header h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 700;
	color: #111827;
	letter-spacing: -0.01em;
}

.apb-plan__badge {
	padding: 4px 10px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	background: #f3f4f6;
	color: #4b5563;
	border-radius: 9999px;
	letter-spacing: 0.02em;
}

.apb-plan__desc {
	margin: 0 0 20px;
	font-size: 15px;
	color: #4b5563;
	line-height: 1.6;
}

.apb-plan__section {
	margin-bottom: 20px;
}

.apb-plan__section:last-child {
	margin-bottom: 0;
}

.apb-plan__section h4 {
	margin: 0 0 10px;
	font-size: 14px;
	font-weight: 600;
	color: #1f2937;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

.apb-plan__section ul {
	margin: 0;
	padding-left: 20px;
	font-size: 14px;
	color: #4b5563;
	line-height: 1.6;
}

.apb-plan__section li {
	margin-bottom: 8px;
}

.apb-plan__section code {
	background: #f3f4f6;
	color: #ef4444;
	padding: 2px 6px;
	border-radius: 4px;
	font-size: 13px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.apb-plan__main {
	display: inline-block;
	padding: 2px 6px;
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	background: #3b82f6;
	color: #ffffff;
	border-radius: 4px;
	margin-left: 6px;
	vertical-align: middle;
}

.apb-plan__tags {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}

.apb-plan__tags code {
	background: #f3f4f6;
	color: #1f2937;
	padding: 4px 10px;
	border-radius: 6px;
	font-size: 13px;
	font-weight: 500;
	border: 1px solid #e5e7eb;
}
</style>
