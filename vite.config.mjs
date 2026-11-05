import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

// Builds the bundle shipped with the Composer package:
//   public/celerik.js     ES module entry (registry + lazy initializers)
//   public/chunks/*.js    one chunk per component library, loaded only when used
//   public/celerik.css
// jQuery is a peer dependency: `jquery` resolves to the page's window.jQuery.
//
// A regular build is used rather than library mode, which never minifies ES output.
export default defineConfig({
  publicDir: false,
  base: './',
  resolve: {
    alias: {
      jquery: fileURLToPath(new URL('./resources/js/jquery-global.js', import.meta.url)),
    },
  },
  build: {
    outDir: 'public',
    emptyOutDir: true,
    sourcemap: true,
    modulePreload: false,
    // CSS imported by a lazy chunk (Quill, FilePond) is loaded with that chunk only.
    cssCodeSplit: true,
    // CommonJS libraries (Parsley, parts of DataTables) call require('jquery'): give them
    // the shim's default export (window.jQuery), not its module namespace object.
    commonjsOptions: {
      requireReturnsDefault: 'auto',
    },
    rollupOptions: {
      input: 'resources/js/index.js',
      preserveEntrySignatures: 'exports-only',
      output: {
        entryFileNames: 'celerik.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        // The entry stylesheet keeps its stable name; chunk stylesheets are hashed.
        assetFileNames: (asset) => (asset.names?.includes('index.css') ? 'celerik.css' : 'chunks/[name]-[hash][extname]'),
      },
    },
  },
});
