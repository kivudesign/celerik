import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

// Builds the bundle shipped with the Composer package:
//   public/celerik.js     ES module entry (registry + lazy initializers)
//   public/chunks/*.js    one chunk per component library, loaded only when used
//   public/celerik.css
//   public/celerik-material.css, public/celerik-tailwind.css   optional themes, loaded after celerik.css
// jQuery is a peer dependency: `jquery` resolves to the page's window.jQuery.
//
// A regular build is used rather than library mode, which never minifies ES output.
// Rollup emits an empty JS file for each CSS-only entry (the themes): remove them.
const dropCssEntryStubs = () => ({
  name: 'celerik-drop-css-entry-stubs',
  generateBundle(_, bundle) {
    for (const [file, output] of Object.entries(bundle)) {
      if (output.type === 'chunk' && output.isEntry && output.name.startsWith('celerik-')) {
        delete bundle[file];
        delete bundle[`${file}.map`];
      }
    }
  },
});

export default defineConfig({
  plugins: [dropCssEntryStubs()],
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
      input: {
        index: 'resources/js/index.js',
        'celerik-material': 'resources/scss/themes/material.scss',
        'celerik-tailwind': 'resources/scss/themes/tailwind.scss',
      },
      preserveEntrySignatures: 'exports-only',
      output: {
        // Theme entries are CSS only; their empty JS stubs are dropped by dropCssEntryStubs().
        entryFileNames: (chunk) => (chunk.name === 'index' ? 'celerik.js' : 'chunks/[name]-[hash].js'),
        chunkFileNames: 'chunks/[name]-[hash].js',
        // The entry stylesheet keeps its stable name; chunk stylesheets are hashed.
        assetFileNames: (asset) => {
          if (asset.names?.includes('index.css')) {
            return 'celerik.css';
          }
          const theme = asset.names?.find((name) => /^celerik-(material|tailwind)\.css$/.test(name));
          return theme ?? 'chunks/[name]-[hash][extname]';
        },
      },
    },
  },
});
