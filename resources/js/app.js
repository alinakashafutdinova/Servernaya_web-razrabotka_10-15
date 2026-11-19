import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';

const mountPoint = document.getElementById('app');

if (mountPoint) {
    createApp(App).mount(mountPoint);
}
