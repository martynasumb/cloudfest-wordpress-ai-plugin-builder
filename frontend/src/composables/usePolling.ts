import { ref, onUnmounted } from 'vue';

/**
 * Generic polling composable.
 * @param callback  Async function called each tick. Return `true` to stop polling.
 * @param intervalMs  Milliseconds between ticks (default 2 000).
 */
export function usePolling(callback: () => Promise<boolean>, intervalMs: number = 2000) {
	const isPolling = ref(false);
	let timer: ReturnType<typeof setInterval> | null = null;

	async function start() {
		if (isPolling.value) return;
		isPolling.value = true;

		// Run immediately first, then schedule interval.
		try {
			const shouldStop = await callback();
			if (shouldStop) {
				isPolling.value = false;
				return;
			}
		} catch {
			isPolling.value = false;
			return;
		}

		timer = setInterval(async () => {
			try {
				const done = await callback();
				if (done) stop();
			} catch {
				stop();
			}
		}, intervalMs);
	}

	function stop() {
		isPolling.value = false;
		if (timer) {
			clearInterval(timer);
			timer = null;
		}
	}

	onUnmounted(stop);

	return { isPolling, start, stop };
}
