// Copies the few runtime JS libraries from node_modules into public/assets/vendor,
// so production needs no CDN and the service worker can cache them.
import { copyFileSync, mkdirSync } from 'node:fs';

const files = {
  'node_modules/htmx.org/dist/htmx.min.js': 'public/assets/vendor/htmx.min.js',
  'node_modules/alpinejs/dist/cdn.min.js': 'public/assets/vendor/alpine.min.js',
};

mkdirSync('public/assets/vendor', { recursive: true });
for (const [from, to] of Object.entries(files)) {
  copyFileSync(from, to);
  console.log(`${from} -> ${to}`);
}
