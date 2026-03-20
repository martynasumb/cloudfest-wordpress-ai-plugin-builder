import type { GenerateResponse, GeneratedFile, InstallResponse, StatusResponse, PluginPlan } from '../types';

declare global {
	interface Window {
		aiPluginBuilder: {
			restUrl: string;
			nonce: string;
			adminUrl: string;
		};
	}
}

const getConfig = () => window.aiPluginBuilder;

async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
	const config = getConfig();
	const url = config.restUrl + path;

	const response = await fetch(url, {
		...options,
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
			...(options.headers || {}),
		},
	});

	if (!response.ok) {
		const error = await response.json().catch(() => ({ message: response.statusText }));
		throw new Error(error.message || `Request failed: ${response.status}`);
	}

	return response.json();
}

export async function generate(
	description: string,
	complexity: string = 'simple',
	previousPlan?: PluginPlan | null,
	previousFiles?: GeneratedFile[] | null,
): Promise<GenerateResponse> {
	const body: Record<string, unknown> = { description, complexity };

	if (previousPlan) {
		body.previous_plan = previousPlan;
	}
	if (previousFiles) {
		body.previous_files = previousFiles;
	}

	return apiFetch<GenerateResponse>('generate', {
		method: 'POST',
		body: JSON.stringify(body),
	});
}

export async function getStatus(jobId: string): Promise<StatusResponse> {
	// Add cache-busting timestamp to prevent server/CDN caching.
	const cacheBuster = `_t=${Date.now()}`;
	return apiFetch<StatusResponse>(`status/${jobId}?${cacheBuster}`, {
		cache: 'no-store',
	});
}

export async function install(
	pluginSlug: string,
	files: GeneratedFile[],
	force: boolean = false,
): Promise<InstallResponse> {
	return apiFetch<InstallResponse>('install', {
		method: 'POST',
		body: JSON.stringify({
			plugin_slug: pluginSlug,
			files: files.map((f) => ({
				path: f.path,
				content: f.content,
				is_main: f.is_main,
			})),
			force,
		}),
	});
}
