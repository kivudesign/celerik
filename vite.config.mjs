import { defineConfig } from 'vite';

// Builds the single bundle shipped with the Composer package:
//   public/celerik.js  (IIFE, exposes window.Celerik)
//   public/celerik.css
// jQuery is a peer dependency: the page must load it before celerik.js.
export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public',
    emptyOutDir: true,
    sourcemap: true,
    lib: {
      entry: 'resources/js/index.js',
      name: 'Celerik',
      formats: ['iife'],
      fileName: () => 'celerik.js',
      cssFileName: 'celerik',
    },
    rollupOptions: {
      external: ['jquery'],
      output: {
        globals: { jquery: 'jQuery' },
      },
    },
  },
});
