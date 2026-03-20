<template>
	<div :class="['apb-review', review.passed ? 'apb-review--pass' : 'apb-review--warn']">
		<div class="apb-review__header">
			<span class="apb-review__icon">{{ review.passed ? '&#10003;' : '&#9888;' }}</span>
			<strong>{{ review.passed ? 'Review Passed' : 'Review Complete (with warnings)' }}</strong>
		</div>

		<p class="apb-review__summary">{{ review.review_summary }}</p>

		<div v-if="review.suggestions.length" class="apb-review__suggestions">
			<div v-for="(s, i) in review.suggestions" :key="i" class="apb-review__suggestion">
				<div class="apb-review__suggestion-header">
					<code>{{ s.file_path }}</code>
					<span class="apb-review__reason">{{ s.reason }}</span>
				</div>
				<p>{{ s.description }}</p>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import type { ReviewResult } from '../types';

defineProps<{ review: ReviewResult }>();
</script>

<style scoped>
.apb-review {
	width: 100%;
	max-width: 650px;
	border-radius: 12px;
	padding: 20px;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.apb-review--pass {
	background: #ecfdf5;
	border: 1px solid #a7f3d0;
}

.apb-review--warn {
	background: #fffbeb;
	border: 1px solid #fde68a;
}

.apb-review__header {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 12px;
	font-size: 16px;
}

.apb-review--pass .apb-review__icon {
	color: #059669;
	font-size: 20px;
	font-weight: bold;
}

.apb-review--warn .apb-review__icon {
	color: #d97706;
	font-size: 20px;
	font-weight: bold;
}

.apb-review--pass strong {
	color: #065f46;
}

.apb-review--warn strong {
	color: #92400e;
}

.apb-review__summary {
	margin: 0 0 16px;
	font-size: 14px;
	color: #4b5563;
	line-height: 1.6;
}

.apb-review__suggestions {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.apb-review__suggestion {
	background: rgba(255, 255, 255, 0.7);
	border-radius: 8px;
	padding: 12px 16px;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.apb-review__suggestion-header {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 8px;
}

.apb-review__suggestion-header code {
	font-size: 13px;
	background: #f3f4f6;
	color: #1f2937;
	padding: 2px 8px;
	border-radius: 4px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.apb-review__reason {
	font-size: 13px;
	font-weight: 600;
	color: #b45309;
}

.apb-review__suggestion p {
	margin: 0;
	font-size: 14px;
	color: #4b5563;
	line-height: 1.5;
}
</style>
