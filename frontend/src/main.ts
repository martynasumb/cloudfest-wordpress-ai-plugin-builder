import { createApp } from 'vue';
import App from './App.vue';

const mountEl = document.getElementById('wordpress-ai-plugin-builder-app');
if (mountEl) {
	createApp(App).mount(mountEl);
}
