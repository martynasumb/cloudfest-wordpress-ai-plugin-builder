<template>
	<div class="apb-chat">
		<div class="apb-chat__header">
			<h2>WordPress AI Plugin Builder</h2>
			<div class="apb-chat__header-actions">
				<button v-if="messages.length" class="apb-chat__reset" @click="reset">
					New Chat
				</button>
				<div v-else class="apb-chat__status">
					<div class="apb-chat__status-dot"></div>
					Ready
				</div>
			</div>
		</div>

		<div ref="messagesEl" class="apb-chat__messages">
			<div v-if="!messages.length" class="apb-chat__empty">
				<div class="apb-chat__empty-icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
					</svg>
				</div>
				<h3 class="apb-chat__empty-title">WordPress AI Plugin Builder</h3>
				<p class="apb-chat__empty-subtitle">Describe the WordPress plugin you want to build and AI will generate it for you.</p>

				<div class="apb-chat__how-it-works">
					<div class="apb-chat__step">
						<span class="apb-chat__step-num">1</span>
						<span>Describe your plugin idea</span>
					</div>
					<div class="apb-chat__step">
						<span class="apb-chat__step-num">2</span>
						<span>AI generates the code</span>
					</div>
					<div class="apb-chat__step">
						<span class="apb-chat__step-num">3</span>
						<span>Review and install</span>
					</div>
				</div>

				<p class="apb-chat__examples-label">Try an example:</p>
				<div class="apb-chat__examples">
					<button
						v-for="example in examplePrompts"
						:key="example"
						class="apb-chat__example-btn"
						@click="useExample(example)"
					>
						{{ example }}
					</button>
				</div>

				<p class="apb-chat__tip">
					<strong>Tip:</strong> Be specific about features, settings, and where things should appear (admin, frontend, dashboard widget, etc.)
				</p>
			</div>

			<ChatMessage
				v-for="msg in messages"
				:key="msg.id"
				:message="msg"
				@install="installPlugin"
			/>

			<!-- Install Anyway button when there's a slug conflict -->
			<div v-if="hasSlugConflict" class="apb-chat__conflict-actions">
				<button class="apb-chat__force-install" @click="forceInstallPlugin">
					Install Anyway
				</button>
			</div>
		</div>

		<div class="apb-chat__footer">
			<ActivityLog :logs="logs" :token-usage="tokenUsage" />

			<div class="apb-chat__input-wrapper">
				<textarea
					v-model="input"
					class="apb-chat__input"
					:disabled="isProcessing"
					rows="1"
					placeholder="Describe what plugin you want to build..."
					@keydown.enter.exact.prevent="send"
				/>
				<button class="apb-chat__send-btn" :disabled="isProcessing || !input.trim()" @click="send" aria-label="Send message">
					<svg v-if="isProcessing" class="apb-spinner-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					<svg v-else viewBox="0 0 24 24">
						<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
					</svg>
				</button>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { usePluginBuilder } from '../composables/usePluginBuilder';
import ChatMessage from './ChatMessage.vue';
import ActivityLog from './ActivityLog.vue';

const { messages, logs, tokenUsage, isProcessing, hasSlugConflict, sendDescription, installPlugin, forceInstallPlugin, reset } = usePluginBuilder();

const input = ref('');
const messagesEl = ref<HTMLElement | null>(null);

const examplePrompts = [
	'A dashboard widget showing recent drafts with quick edit links',
	'A plugin that adds reading time to blog posts',
	'A simple contact form with email notifications',
	'A maintenance mode plugin with countdown timer',
];

function useExample(example: string) {
	input.value = example;
}

function send() {
	if (!input.value.trim() || isProcessing.value) return;
	sendDescription(input.value.trim());
	input.value = '';
}

// Auto-scroll to bottom when messages change.
watch(
	() => messages.value.length,
	async () => {
		await nextTick();
		if (messagesEl.value) {
			messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
		}
	},
);
</script>

<style scoped>
.apb-chat {
	--bg-color: #f3f4f6;
	--card-bg: #ffffff;
	--text-main: #111827;
	--text-muted: #6b7280;
	--primary-color: #6366f1;
	--primary-hover: #4f46e5;
	--ai-bubble-bg: #f9fafb;
	--ai-bubble-border: #e5e7eb;
	--border-radius: 12px;

	display: flex;
	flex-direction: column;
	height: calc(100vh - 100px);
	max-width: 900px;
	margin: 20px auto;
	background: var(--card-bg);
	border-radius: var(--border-radius);
	box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 4px 6px rgba(0, 0, 0, 0.02);
	overflow: hidden;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.apb-chat__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20px 24px;
	border-bottom: 1px solid var(--ai-bubble-border);
	background: var(--card-bg);
}

.apb-chat__header h2 {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
	color: var(--text-main);
}

.apb-chat__header-actions {
	display: flex;
	align-items: center;
}

.apb-chat__status {
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	color: var(--text-muted);
}

.apb-chat__status-dot {
	width: 8px;
	height: 8px;
	background-color: #10b981;
	border-radius: 50%;
}

.apb-chat__reset {
	padding: 6px 14px;
	font-size: 13px;
	font-weight: 500;
	color: var(--text-muted);
	background: #f3f4f6;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.2s ease;
}

.apb-chat__reset:hover {
	background: #e5e7eb;
	color: var(--text-main);
}

.apb-chat__messages {
	flex: 1;
	overflow-y: auto;
	padding: 24px;
	display: flex;
	flex-direction: column;
	gap: 24px;
}

/* Custom Scrollbar */
.apb-chat__messages::-webkit-scrollbar {
	width: 6px;
}
.apb-chat__messages::-webkit-scrollbar-track {
	background: transparent;
}
.apb-chat__messages::-webkit-scrollbar-thumb {
	background-color: #d1d5db;
	border-radius: 10px;
}

.apb-chat__empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	text-align: center;
	color: var(--text-muted);
	animation: fadeIn 0.5s ease-out;
	padding: 20px;
}

.apb-chat__empty-icon {
	width: 56px;
	height: 56px;
	margin-bottom: 16px;
	color: var(--primary-color);
	opacity: 0.8;
}

.apb-chat__empty-icon svg {
	width: 100%;
	height: 100%;
}

.apb-chat__empty-title {
	margin: 0 0 8px;
	font-size: 20px;
	font-weight: 600;
	color: var(--text-main);
}

.apb-chat__empty-subtitle {
	margin: 0 0 24px;
	font-size: 14px;
	color: var(--text-muted);
	max-width: 400px;
	line-height: 1.5;
}

.apb-chat__how-it-works {
	display: flex;
	gap: 24px;
	margin-bottom: 28px;
	padding: 16px 24px;
	background: #f9fafb;
	border-radius: 12px;
}

.apb-chat__step {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 13px;
	color: var(--text-muted);
}

.apb-chat__step-num {
	width: 22px;
	height: 22px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: var(--primary-color);
	color: white;
	font-size: 11px;
	font-weight: 600;
	border-radius: 50%;
}

.apb-chat__examples-label {
	margin: 0 0 12px;
	font-size: 13px;
	font-weight: 500;
	color: var(--text-muted);
}

.apb-chat__examples {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: center;
	max-width: 600px;
	margin-bottom: 20px;
}

.apb-chat__example-btn {
	padding: 8px 14px;
	font-size: 13px;
	color: var(--text-main);
	background: white;
	border: 1px solid #e5e7eb;
	border-radius: 20px;
	cursor: pointer;
	transition: all 0.2s ease;
}

.apb-chat__example-btn:hover {
	border-color: var(--primary-color);
	color: var(--primary-color);
	background: #f5f3ff;
}

.apb-chat__tip {
	margin: 0;
	padding: 12px 16px;
	font-size: 12px;
	color: var(--text-muted);
	background: #fffbeb;
	border: 1px solid #fef3c7;
	border-radius: 8px;
	max-width: 500px;
}

.apb-chat__tip strong {
	color: #92400e;
}

.apb-chat__footer {
	padding: 16px 24px 24px;
	border-top: 1px solid var(--ai-bubble-border);
	background: var(--card-bg);
}

.apb-chat__input-wrapper {
	display: flex;
	align-items: flex-end;
	gap: 12px;
	background: #f9fafb;
	border: 1px solid #d1d5db;
	border-radius: 24px;
	padding: 8px 8px 8px 20px;
	transition: border-color 0.2s;
}

.apb-chat__input-wrapper:focus-within {
	border-color: var(--primary-color);
	background: #ffffff;
}

.apb-chat__input {
	flex: 1;
	border: none;
	background: transparent;
	padding: 8px 0;
	font-size: 15px;
	color: var(--text-main);
	outline: none;
	resize: none;
	max-height: 120px;
	font-family: inherit;
	line-height: 1.5;
}

.apb-chat__input::placeholder {
	color: #9ca3af;
}

.apb-chat__input:disabled {
	color: #9ca3af;
	cursor: not-allowed;
}

.apb-chat__send-btn {
	background: var(--primary-color);
	color: white;
	border: none;
	width: 40px;
	height: 40px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: background-color 0.2s;
	flex-shrink: 0;
}

.apb-chat__send-btn:hover:not(:disabled) {
	background: var(--primary-hover);
}

.apb-chat__send-btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
	background: #9ca3af;
}

.apb-chat__send-btn svg {
	width: 18px;
	height: 18px;
	fill: currentColor;
}

.apb-spinner-svg {
	animation: apb-spin 1s linear infinite;
	color: white;
}

.opacity-25 {
	opacity: 0.25;
}

.opacity-75 {
	opacity: 0.75;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
}

@keyframes apb-spin {
	to {
		transform: rotate(360deg);
	}
}

.apb-chat__conflict-actions {
	display: flex;
	justify-content: flex-start;
	padding: 12px 0;
	margin-left: 48px;
}

.apb-chat__force-install {
	padding: 10px 20px;
	font-size: 14px;
	font-weight: 600;
	color: #ffffff;
	background: #f59e0b;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.2s ease;
	box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2);
}

.apb-chat__force-install:hover {
	background: #d97706;
	transform: translateY(-1px);
	box-shadow: 0 6px 8px -1px rgba(245, 158, 11, 0.3);
}
</style>
