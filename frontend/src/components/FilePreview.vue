<template>
	<div class="apb-files">
		<!-- Tab bar -->
		<div class="apb-files__tabs">
			<button
				v-for="(file, i) in files"
				:key="file.path"
				:class="['apb-files__tab', { 'apb-files__tab--active': activeIndex === i }]"
				@click="activeIndex = i"
			>
				{{ file.path }}
				<span v-if="file.is_main" class="apb-files__main">main</span>
			</button>
		</div>

		<!-- Code view -->
		<div v-if="activeFile" class="apb-files__content">
			<div class="apb-files__meta">
				<span>{{ activeFile.description }}</span>
				<button class="apb-files__copy" @click="copy">
					{{ copied ? 'Copied!' : 'Copy' }}
				</button>
			</div>
			<pre class="apb-files__code"><code v-html="highlightedCode" /></pre>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import 'highlight.js/styles/github.css';
import type { GeneratedFile } from '../types';

hljs.registerLanguage('php', php);
hljs.registerLanguage('css', css);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('js', javascript);
hljs.registerLanguage('json', json);

const props = defineProps<{ files: GeneratedFile[] }>();

const activeIndex = ref(0);
const copied = ref(false);

const activeFile = computed(() => props.files[activeIndex.value]);

const highlightedCode = computed(() => {
	if (!activeFile.value) return '';
	const lang = activeFile.value.type === 'js' ? 'javascript' : activeFile.value.type;
	try {
		return hljs.highlight(activeFile.value.content, { language: lang }).value;
	} catch {
		return activeFile.value.content
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}
});

// Reset tab when files change.
watch(() => props.files, () => { activeIndex.value = 0; });

async function copy() {
	if (!activeFile.value) return;
	await navigator.clipboard.writeText(activeFile.value.content);
	copied.value = true;
	setTimeout(() => { copied.value = false; }, 2000);
}
</script>

<style scoped>
.apb-files {
	width: 100%;
	max-width: 750px;
	background: #ffffff;
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.apb-files__tabs {
	display: flex;
	flex-wrap: wrap;
	background: #f9fafb;
	border-bottom: 1px solid #e5e7eb;
	padding: 4px 8px 0;
	gap: 4px;
}

.apb-files__tab {
	padding: 10px 16px;
	font-size: 13px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
	background: transparent;
	border: none;
	border-bottom: 2px solid transparent;
	color: #6b7280;
	cursor: pointer;
	border-radius: 6px 6px 0 0;
	transition: all 0.2s;
	margin-bottom: -1px;
}

.apb-files__tab:hover {
	color: #111827;
	background: #f3f4f6;
}

.apb-files__tab--active {
	color: #2563eb;
	border-bottom-color: #2563eb;
	font-weight: 600;
	background: #ffffff;
	border-top: 1px solid #e5e7eb;
	border-left: 1px solid #e5e7eb;
	border-right: 1px solid #e5e7eb;
}

.apb-files__main {
	display: inline-block;
	padding: 2px 6px;
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	background: #3b82f6;
	color: #ffffff;
	border-radius: 4px;
	margin-left: 6px;
	vertical-align: middle;
}

.apb-files__content {
	display: flex;
	flex-direction: column;
}

.apb-files__meta {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 12px 20px;
	font-size: 13px;
	color: #4b5563;
	background: #ffffff;
	border-bottom: 1px solid #f3f4f6;
}

.apb-files__copy {
	padding: 6px 14px;
	font-size: 13px;
	font-weight: 500;
	background: #ffffff;
	border: 1px solid #d1d5db;
	border-radius: 6px;
	color: #374151;
	cursor: pointer;
	transition: all 0.2s ease;
	box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.apb-files__copy:hover {
	background: #f9fafb;
	border-color: #9ca3af;
}

.apb-files__code {
	margin: 0;
	padding: 20px;
	overflow-x: auto;
	font-size: 14px;
	line-height: 1.6;
	max-height: 550px;
	overflow-y: auto;
	background: #111827;
	color: #f3f4f6;
}

.apb-files__code code {
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* Scrollbar for code block */
.apb-files__code::-webkit-scrollbar {
	width: 8px;
	height: 8px;
}
.apb-files__code::-webkit-scrollbar-track {
	background: #1f2937;
}
.apb-files__code::-webkit-scrollbar-thumb {
	background-color: #4b5563;
	border-radius: 4px;
}
</style>
