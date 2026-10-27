// Copies browser builds of JS libraries into public/assets/vendor (committed).
import { cpSync, mkdirSync } from 'node:fs';

const out = 'public/assets/vendor';
const files = {
  'alpinejs/dist/cdn.min.js': 'alpine.min.js',
  'lucide/dist/umd/lucide.min.js': 'lucide.min.js',
  'chart.js/dist/chart.umd.min.js': 'chart.umd.min.js',
  'leaflet/dist/leaflet.js': 'leaflet/leaflet.js',
  'leaflet/dist/leaflet.css': 'leaflet/leaflet.css',
  'leaflet/dist/images': 'leaflet/images',
};
mkdirSync(`${out}/leaflet`, { recursive: true });
for (const [from, to] of Object.entries(files)) {
  cpSync(`node_modules/${from}`, `${out}/${to}`, { recursive: true });
}
console.log('vendor files copied');
