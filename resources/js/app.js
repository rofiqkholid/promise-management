import './bootstrap';
import './tooltip';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';
import Viewer from 'viewerjs';
import { marked } from 'marked';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

window.Chart = Chart;
window.Viewer = Viewer;
window.marked = marked;
