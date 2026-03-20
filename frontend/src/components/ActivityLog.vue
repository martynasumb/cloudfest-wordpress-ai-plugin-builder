<template>
	<div class="apb-log">
		<button class="apb-log__toggle" @click="open = !open">
			<span class="apb-log__arrow">{{ open ? '&#9660;' : '&#9654;' }}</span>
			Activity Log
			<span class="apb-log__count">{{ logs.length }}</span>
			<span v-if="latestError" class="apb-log__error-dot" title="Has errors" />
		</button>

		<div v-if="open" ref="logEl" class="apb-log__panel">
			<div v-if="!logs.length" class="apb-log__empty">No activity yet.</div>
			<div v-for="entry in logs" :key="entry.id" :class="['apb-log__entry', `apb-log__entry--${entry.level}`]">
				<span class="apb-log__time">{{ formatTime(entry.timestamp) }}</span>
				<span class="apb-log__icon">{{ icons[entry.level] }}</span>
				<span class="apb-log__msg">{{ entry.message }}</span>
				<span v-if="entry.detail" class="apb-log__detail">{{ entry.detail }}</span>
			</div>

			<!-- Token usage summary -->
			<div v-if="tokenUsage" class="apb-log__tokens">
				<div class="apb-log__tokens-header">Token Usage</div>
				<div class="apb-log__tokens-row">
					<span>Total: <strong>{{ tokenUsage.total_tokens.toLocaleString() }}</strong></span>
					<span>In: {{ tokenUsage.total_input_tokens.toLocaleString() }}</span>
					<span>Out: {{ tokenUsage.total_output_tokens.toLocaleString() }}</span>
				</div>
				<div v-for="(step, i) in tokenUsage.steps" :key="i" class="apb-log__tokens-step">
					<span>{{ step.step }}</span>
					<span>{{ step.model }}</span>
					<span>{{ (step.input_tokens + step.output_tokens).toLocaleString() }} tok</span>
					<span v-if="step.continued">{{ step.continued }} cont.</span>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import type { LogEntry, TokenUsageSummary } from '../types';

const props = defineProps<{
	logs: LogEntry[];
	tokenUsage: TokenUsageSummary | null;
}>();

const open = ref(false);
const logEl = ref<HTMLElement | null>(null);

const icons: Record<string, string> = {
	info: '\u2139',
	warn: '\u26A0',
	error: '\u2717',
	success: '\u2713',
};

const latestError = computed(() => props.logs.some((l) => l.level === 'error'));

function formatTime(d: Date): string {
	return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// Auto-open on error.
watch(latestError, (hasError) => {
	if (hasError) open.value = true;
});

// Auto-scroll log panel.
watch(
	() => props.logs.length,
	async () => {
		await nextTick();
		if (logEl.value) {
			logEl.value.scrollTop = logEl.value.scrollHeight;
		}
	},
);
</script>

<style scoped>
.apb-log {
	border-top: 1px solid #f3f4f6;
	background: #ffffff;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.apb-log__toggle {
	display: flex;
	align-items: center;
	gap: 8px;
	width: 100%;
	padding: 12px 24px;
	font-size: 13px;
	font-weight: 600;
	color: #6b7280;
	background: none;
	border: none;
	cursor: pointer;
	text-align: left;
	transition: all 0.2s;
}

.apb-log__toggle:hover {
	color: #111827;
	background: #f9fafb;
}

.apb-log__arrow {
	font-size: 10px;
	transition: transform 0.2s;
}

.apb-log__count {
	background: #f3f4f6;
	color: #4b5563;
	font-size: 11px;
	padding: 2px 8px;
	border-radius: 9999px;
	font-weight: 600;
}

.apb-log__error-dot {
	width: 8px;
	height: 8px;
	background: #ef4444;
	border-radius: 50%;
	margin-left: 4px;
	box-shadow: 0 0 0 2px #fee2e2;
}

.apb-log__panel {
	max-height: 250px;
	overflow-y: auto;
	padding: 0 24px 16px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
	font-size: 12px;
	line-height: 1.6;
	background: #f9fafb;
	border-top: 1px solid #f3f4f6;
}

/* Scrollbar */
.apb-log__panel::-webkit-scrollbar { width: 6px; }
.apb-log__panel::-webkit-scrollbar-track { background: transparent; }
.apb-log__panel::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }

.apb-log__empty {
	color: #9ca3af;
	font-style: italic;
	padding-top: 12px;
}

.apb-log__entry {
	display: flex;
	align-items: baseline;
	gap: 8px;
	padding: 4px 0;
}

.apb-log__time {
	color: #9ca3af;
	flex-shrink: 0;
}

.apb-log__icon {
	flex-shrink: 0;
	width: 16px;
	text-align: center;
	font-weight: bold;
}

.apb-log__entry--info .apb-log__icon { color: #3b82f6; }
.apb-log__entry--warn .apb-log__icon { color: #d97706; }
.apb-log__entry--error .apb-log__icon { color: #dc2626; }
.apb-log__entry--success .apb-log__icon { color: #059669; }

.apb-log__msg {
	color: #374151;
}

.apb-log__entry--error .apb-log__msg {
	color: #dc2626;
	font-weight: 500;
}

.apb-log__detail {
	color: #6b7280;
	margin-left: 4px;
}

.apb-log__detail::before {
	content: '\2014 ';
}

.apb-log__tokens {
	margin-top: 12px;
	padding-top: 12px;
	border-top: 1px dashed #e5e7eb;
}

.apb-log__tokens-header {
	font-weight: 600;
	color: #1f2937;
	margin-bottom: 6px;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

.apb-log__tokens-row {
	display: flex;
	gap: 16px;
	color: #4b5563;
	margin-bottom: 4px;
}

.apb-log__tokens-row strong {
	color: #111827;
}

.apb-log__tokens-step {
	display: flex;
	gap: 12px;
	color: #6b7280;
	padding-left: 8px;
	font-size: 11px;
}
</style>
