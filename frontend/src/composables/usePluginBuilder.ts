import { ref, computed } from 'vue';
import type {
	BuilderState,
	ChatMessage,
	GeneratedFile,
	InstallResponse,
	LogEntry,
	LogLevel,
	PluginPlan,
	ReviewResult,
	StatusResponse,
	TokenUsageSummary,
} from '../types';
import { isJobResponse, needsSlugConfirmation } from '../types';
import * as api from '../repositories/pluginBuilderRepository';
import { usePolling } from './usePolling';

let messageId = 0;
let logId = 0;

function createMessage(
	role: 'user' | 'assistant',
	type: ChatMessage['type'],
	content: string,
	data?: ChatMessage['data'],
): ChatMessage {
	return {
		id: String(++messageId),
		role,
		type,
		content,
		data,
		timestamp: new Date(),
	};
}

export function usePluginBuilder() {
	const state = ref<BuilderState>('idle');
	const messages = ref<ChatMessage[]>([]);
	const logs = ref<LogEntry[]>([]);
	const currentJobId = ref<string | null>(null);
	const currentPlan = ref<PluginPlan | null>(null);
	const currentFiles = ref<GeneratedFile[]>([]);
	const currentReview = ref<ReviewResult | null>(null);
	const currentStep = ref('');
	const error = ref<string | null>(null);
	const planShown = ref(false);
	const tokenUsage = ref<TokenUsageSummary | null>(null);
	const lastStatus = ref<string>('');
	const startTime = ref<number>(0);

	// --- Logging ---

	function log(level: LogLevel, message: string, detail?: string) {
		logs.value.push({
			id: ++logId,
			timestamp: new Date(),
			level,
			message,
			detail,
		});
	}

	function elapsed(): string {
		if (!startTime.value) return '';
		const secs = Math.round((Date.now() - startTime.value) / 1000);
		return `${secs}s`;
	}

	// --- Messages ---

	function addMessage(msg: ChatMessage) {
		messages.value.push(msg);
	}

	function updateLastLoading(content: string) {
		for (let i = messages.value.length - 1; i >= 0; i--) {
			if (messages.value[i].type === 'loading') {
				messages.value[i].content = content;
				return;
			}
		}
	}

	function removeLastLoading() {
		for (let i = messages.value.length - 1; i >= 0; i--) {
			if (messages.value[i].type === 'loading') {
				messages.value.splice(i, 1);
				return;
			}
		}
	}

	// --- Polling ---

	const { start: startPolling, stop: stopPolling } = usePolling(async () => {
		if (!currentJobId.value) return true;

		try {
			const status = await api.getStatus(currentJobId.value);
			return handleStatusUpdate(status);
		} catch (e: unknown) {
			const msg = e instanceof Error ? e.message : 'Failed to check status';
			log('error', 'Polling failed', msg);
			handleError(msg);
			return true;
		}
	}, 2000);

	function handleStatusUpdate(status: StatusResponse): boolean {
		currentStep.value = status.current_step;

		// Log status transitions.
		if (status.status !== lastStatus.value) {
			const prefix = elapsed() ? `[${elapsed()}]` : '';
			log('info', `${prefix} Status: ${status.status}`, status.current_step);
			lastStatus.value = status.status;
		}

		// Show plan once when it appears (pipeline keeps running).
		if (status.plan && !planShown.value) {
			planShown.value = true;
			currentPlan.value = status.plan;
			removeLastLoading();
			addMessage(
				createMessage('assistant', 'plan', `Here's the plan for **${status.plan.plugin_name}**:`, status.plan),
			);
			addMessage(createMessage('assistant', 'loading', status.current_step));
			log('success', `Plan ready: ${status.plan.plugin_name}`, `${status.plan.files.length} file(s)`);
		}

		// Update loading indicator text.
		updateLastLoading(status.current_step);

		// Map backend status to UI state.
		if (status.status === 'coding') state.value = 'coding';
		if (status.status === 'reviewing') state.value = 'reviewing';
		if (status.status === 'fixing') state.value = 'fixing';

		// Terminal: done.
		if (status.status === 'done') {
			if (status.files?.length) currentFiles.value = status.files;
			if (status.review) currentReview.value = status.review;
			if (status.token_usage) tokenUsage.value = status.token_usage;

			removeLastLoading();

			if (status.review) {
				addMessage(createMessage('assistant', 'review', '', status.review));
			}
			if (status.files?.length) {
				addMessage(createMessage('assistant', 'files', 'Here\'s the generated code:', status.files));
			}

			state.value = 'ready_to_install';

			log('success', `Done in ${elapsed()}`, status.current_step);
			if (status.token_usage) {
				log('info', `Tokens: ${status.token_usage.total_tokens.toLocaleString()} total`,
					`Input: ${status.token_usage.total_input_tokens.toLocaleString()}, Output: ${status.token_usage.total_output_tokens.toLocaleString()}`);
			}
			return true;
		}

		// Terminal: error.
		if (status.status === 'error') {
			handleError(status.error || 'An unknown error occurred');
			return true;
		}

		return false; // keep polling
	}

	function handleError(message: string) {
		state.value = 'error';
		error.value = message;
		removeLastLoading();
		addMessage(createMessage('assistant', 'error', message));
		log('error', 'Pipeline error', message);
	}

	// --- Actions ---

	async function sendDescription(description: string) {
		if (!description.trim()) return;

		// Preserve previous context for potential modification requests.
		const previousPlan = currentPlan.value;
		const previousFiles = currentFiles.value.length > 0 ? currentFiles.value : null;

		// Reset pipeline state for this generation (but keep context).
		stopPolling();
		currentJobId.value = null;
		planShown.value = false;
		error.value = null;
		lastStatus.value = '';
		state.value = 'planning';
		startTime.value = Date.now();

		log('info', 'Request sent', description.substring(0, 100));
		addMessage(createMessage('user', 'text', description));
		addMessage(createMessage('assistant', 'loading', 'Analyzing your request...'));

		try {
			// Pass previous context to the API.
			const resp = await api.generate(description.trim(), 'simple', previousPlan, previousFiles);

			// Check if this is a question/other response (no job dispatched).
			if (!isJobResponse(resp)) {
				removeLastLoading();
				// Show AI response as a text message.
				addMessage(createMessage('assistant', 'text', resp.response || 'I can help you create WordPress plugins. Please describe what plugin you want to build.'));
				if (resp.token_usage) {
					tokenUsage.value = resp.token_usage;
				}
				state.value = previousPlan ? 'ready_to_install' : 'idle';
				log('info', `Intent: ${resp.type}`, resp.response?.substring(0, 100));
				return;
			}

			// Job was dispatched - start polling.
			currentJobId.value = resp.job_id;
			log('info', `Job ID: ${resp.job_id}`, `Intent: ${resp.type}`);

			// If not a modification, clear previous plan/files.
			if (resp.type !== 'modification_request') {
				currentPlan.value = null;
				currentFiles.value = [];
				currentReview.value = null;
			}

			startPolling();
		} catch (e: unknown) {
			const msg = e instanceof Error ? e.message : 'Failed to start generation';
			handleError(msg);
		}
	}

	// Track if we're waiting for slug conflict confirmation.
	const slugConflictWarnings = ref<string[]>([]);

	async function installPlugin(force: boolean = false) {
		if (!currentPlan.value || !currentFiles.value.length) return;

		state.value = 'installing';
		slugConflictWarnings.value = [];
		addMessage(createMessage('assistant', 'loading', 'Installing plugin...'));
		log('info', `Installing: ${currentPlan.value.plugin_slug}${force ? ' (forced)' : ''}`);

		try {
			const result: InstallResponse = await api.install(
				currentPlan.value.plugin_slug,
				currentFiles.value,
				force,
			);

			removeLastLoading();

			// Check if we got a slug conflict warning.
			if (needsSlugConfirmation(result)) {
				slugConflictWarnings.value = result.warnings;
				state.value = 'ready_to_install';
				addMessage(createMessage('assistant', 'text',
					`**Warning:** ${result.warnings.join(' ')}\n\nClick "Install Anyway" to proceed or modify the plugin description to get a different slug.`));
				log('warn', 'Slug conflict detected', result.warnings.join('; '));
				return;
			}

			if ('installed' in result && result.installed) {
				state.value = 'installed';
				addMessage(createMessage('assistant', 'install', '', result));
				log('success', result.activated ? 'Plugin installed & activated' : 'Plugin installed (activation failed)',
					result.error || result.plugin);
			} else if ('error' in result) {
				handleError(result.error || 'Installation failed');
			}
		} catch (e: unknown) {
			const msg = e instanceof Error ? e.message : 'Failed to install plugin';
			handleError(msg);
		}
	}

	function forceInstallPlugin() {
		installPlugin(true);
	}

	function reset() {
		stopPolling();
		state.value = 'idle';
		messages.value = [];
		logs.value = [];
		currentJobId.value = null;
		currentPlan.value = null;
		currentFiles.value = [];
		currentReview.value = null;
		currentStep.value = '';
		planShown.value = false;
		error.value = null;
		tokenUsage.value = null;
		lastStatus.value = '';
		startTime.value = 0;
		slugConflictWarnings.value = [];
		messageId = 0;
		logId = 0;
	}

	const isProcessing = computed(() =>
		['planning', 'coding', 'reviewing', 'fixing', 'installing'].includes(state.value),
	);

	// Computed to check if there's an active slug conflict warning.
	const hasSlugConflict = computed(() => slugConflictWarnings.value.length > 0);

	return {
		state,
		messages,
		logs,
		currentPlan,
		currentFiles,
		currentReview,
		currentStep,
		error,
		tokenUsage,
		isProcessing,
		hasSlugConflict,
		slugConflictWarnings,
		sendDescription,
		installPlugin,
		forceInstallPlugin,
		reset,
	};
}
