import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

window.Chart = Chart;
