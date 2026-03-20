export type PipelineStatus =
	| 'queued'
	| 'planning'
	| 'plan_ready'
	| 'coding'
	| 'reviewing'
	| 'fixing'
	| 'done'
	| 'error';

export interface PlannedFile {
	path: string;
	type: string;
	description: string;
	is_main: boolean;
}

export interface PluginPlan {
	plugin_name: string;
	plugin_slug: string;
	description: string;
	complexity: string;
	files: PlannedFile[];
	hooks_used: string[];
	wp_apis_used: string[];
	security_notes: string[];
	architecture: string;
}

export interface GeneratedFile {
	path: string;
	type: string;
	content: string;
	description: string;
	is_main: boolean;
}

export interface ReviewSuggestion {
	action: string;
	file_path: string;
	file_type: string;
	reason: string;
	description: string;
}

export interface ReviewResult {
	passed: boolean;
	review_summary: string;
	suggestions: ReviewSuggestion[];
}

export interface TokenUsageStep {
	step: string;
	model: string;
	provider: string;
	input_tokens: number;
	output_tokens: number;
	continued: number;
}

export interface TokenUsageSummary {
	steps: TokenUsageStep[];
	total_input_tokens: number;
	total_output_tokens: number;
	total_tokens: number;
}

// Response types for different intents.
export type IntentType = 'plugin_request' | 'modification_request' | 'question' | 'other';

// Response when intent is a question or other (no job dispatched).
export interface QuestionResponse {
	type: 'question' | 'other';
	response: string;
	token_usage: TokenUsageSummary;
}

// Response when intent is plugin_request or modification_request (job dispatched).
export interface GenerateJobResponse {
	job_id: string;
	status: PipelineStatus;
	type: IntentType;
}

// Combined response type from generate endpoint.
export type GenerateResponse = QuestionResponse | GenerateJobResponse;

// Helper to check if response is a job dispatch.
export function isJobResponse(response: GenerateResponse): response is GenerateJobResponse {
	return 'job_id' in response;
}

export interface StatusResponse {
	job_id: string;
	status: PipelineStatus;
	current_step: string;
	plan: PluginPlan | null;
	files: GeneratedFile[];
	review: ReviewResult | null;
	error: string | null;
	token_usage: TokenUsageSummary | null;
}

// Response when slug has conflicts and needs confirmation.
export interface SlugConflictResponse {
	needs_confirmation: boolean;
	warnings: string[];
	message: string;
}

// Response when plugin is successfully installed.
export interface InstallSuccessResponse {
	installed: boolean;
	activated: boolean;
	plugin: string;
	error?: string;
}

// Combined install response type.
export type InstallResponse = SlugConflictResponse | InstallSuccessResponse;

// Helper to check if response needs slug confirmation.
export function needsSlugConfirmation(response: InstallResponse): response is SlugConflictResponse {
	return 'needs_confirmation' in response && response.needs_confirmation === true;
}

export type ChatMessageType =
	| 'text'
	| 'plan'
	| 'files'
	| 'review'
	| 'install'
	| 'error'
	| 'loading';

export interface ChatMessage {
	id: string;
	role: 'user' | 'assistant';
	type: ChatMessageType;
	content: string;
	data?: PluginPlan | GeneratedFile[] | ReviewResult | InstallResponse;
	timestamp: Date;
}

export type BuilderState =
	| 'idle'
	| 'planning'
	| 'coding'
	| 'reviewing'
	| 'fixing'
	| 'ready_to_install'
	| 'installing'
	| 'installed'
	| 'error';

export type LogLevel = 'info' | 'warn' | 'error' | 'success';

export interface LogEntry {
	id: number;
	timestamp: Date;
	level: LogLevel;
	message: string;
	detail?: string;
}
