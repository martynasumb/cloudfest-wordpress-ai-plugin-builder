<template>
	<div :class="['apb-msg', `apb-msg--${message.role}`]">
		<div v-if="message.role === 'assistant'" class="apb-avatar">&#9881;</div>

		<div class="apb-msg__content">
			<!-- User text -->
			<div v-if="message.type === 'text'" class="apb-bubble">
				{{ message.content }}
			</div>

			<!-- Loading / progress -->
			<div v-else-if="message.type === 'loading'" class="apb-bubble apb-bubble--loading">
				<span class="apb-spinner" />
				{{ message.content }}
			</div>

			<!-- Plan preview -->
			<template v-else-if="message.type === 'plan' && planData">
				<PlanPreview :plan="planData" />
			</template>

			<!-- Generated files -->
			<template v-else-if="message.type === 'files' && filesData">
				<FilePreview :files="filesData" />
				<InstallButton @install="$emit('install')" />
			</template>

			<!-- Review result -->
			<template v-else-if="message.type === 'review' && reviewData">
				<ReviewResult :review="reviewData" />
			</template>

			<!-- Install result -->
			<div v-else-if="message.type === 'install' && installSuccess" class="apb-bubble apb-bubble--success">
				<template v-if="installSuccess.activated">
					Plugin installed and activated successfully!
				</template>
				<template v-else>
					Plugin installed but activation failed: {{ installSuccess.error }}
				</template>
			</div>

			<!-- Error -->
			<div v-else-if="message.type === 'error'" class="apb-bubble apb-bubble--error">
				{{ message.content }}
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ChatMessage, GeneratedFile, InstallResponse, InstallSuccessResponse, PluginPlan, ReviewResult as ReviewResultType } from '../types';
import PlanPreview from './PlanPreview.vue';
import FilePreview from './FilePreview.vue';
import ReviewResult from './ReviewResult.vue';
import InstallButton from './InstallButton.vue';

const props = defineProps<{ message: ChatMessage }>();

defineEmits<{ install: [] }>();

const planData = computed(() => props.message.data as PluginPlan | undefined);
const filesData = computed(() => props.message.data as GeneratedFile[] | undefined);
const reviewData = computed(() => props.message.data as ReviewResultType | undefined);

// Cast to InstallSuccessResponse since install messages only have success data (conflicts are shown as text).
const installSuccess = computed(() => {
	const data = props.message.data as InstallResponse | undefined;
	if (data && 'installed' in data) {
		return data as InstallSuccessResponse;
	}
	return undefined;
});
</script>

<style scoped>
.apb-msg {
	display: flex;
	max-width: 85%;
	gap: 12px;
}

.apb-msg--user {
	align-self: flex-end;
	flex-direction: row-reverse;
	margin-left: auto;
}

.apb-msg--assistant {
	align-self: flex-start;
}

.apb-avatar {
	width: 36px;
	height: 36px;
	background: var(--ai-bubble-border, #e5e7eb);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 18px;
	flex-shrink: 0;
	color: var(--text-muted, #6b7280);
}

.apb-msg__content {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12px;
	max-width: 100%;
}

.apb-msg--user .apb-msg__content {
	align-items: flex-end;
}

.apb-bubble {
	padding: 16px;
	border-radius: 12px;
	font-size: 15px;
	line-height: 1.5;
	word-wrap: break-word;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.apb-msg--assistant .apb-bubble {
	background-color: var(--ai-bubble-bg, #f9fafb);
	border: 1px solid var(--ai-bubble-border, #e5e7eb);
	color: var(--text-main, #111827);
	border-top-left-radius: 4px;
}

.apb-msg--user .apb-bubble {
	background-color: var(--user-bubble-bg, #6366f1);
	color: var(--user-text, #ffffff);
	border-top-right-radius: 4px;
	box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);
}

.apb-bubble--loading {
	display: flex;
	align-items: center;
	gap: 12px;
	color: var(--text-muted, #6b7280) !important;
	font-weight: 500;
}

.apb-bubble--success {
	background: #ecfdf5 !important;
	color: #059669 !important;
	border: 1px solid #a7f3d0 !important;
}

.apb-bubble--error {
	background: #fef2f2 !important;
	color: #dc2626 !important;
	border: 1px solid #fecaca !important;
}

.apb-spinner {
	display: inline-block;
	width: 18px;
	height: 18px;
	border: 2px solid #e5e7eb;
	border-top-color: #6366f1;
	border-radius: 50%;
	animation: apb-spin 0.8s linear infinite;
	flex-shrink: 0;
}

@keyframes apb-spin {
	to {
		transform: rotate(360deg);
	}
}
</style>
