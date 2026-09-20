import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import removeConsole from 'vite-plugin-remove-console';
//import path from 'path';

//import url from 'url'

//const __filename = url.fileURLToPath(import.meta.url);
//const __dirname = path.dirname(__filename);
// import react from '@vitejs/plugin-react';
// import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/js/slider.ts'],
      refresh: true,
      assets: [
        'resources/img/**',
        'resources/assets/fonts/**',
      ],
    }),
  ],
  worker: {
    format: 'es',
    plugins: () => [removeConsole({
      custom: [
        "console.info()",
        "console.log()",
        "console.debug()",
        "console.dir()",
        "debugger",
      ]
    })
    ],
  },
  resolve: {
    alias: {
      '@': '/resources/js',
      //'~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
      //'~jquery': path.resolve(__dirname, 'node_modules/jquery'),
      //'~dayjs': path.resolve(__dirname, 'node_modules/dayjs')
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        quietDeps: true,         // Silences warnings from node_modules
        // Specifically ignores the new 'import' and 'if-function' deprecations
        silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
        additionalData:/*
          `$background-color: ${process.env.VITE_SLIDES_BACKGROUND_COLOR || '#000000'
          };
                   $text-color: ${process.env.VITE_SLIDES_TEXT_COLOR || '#FFFFFF'
          };
                   $main-color: ${process.env.VITE_SLIDES_MAIN_COLOR || '#FF0000'
          };`,*/
          `$background-color: '#000000';
          $text-color: '#FFFFFF';
          $main-color:'#FF0000';`
      },
    }
  },
  optimizeDeps: {
    exclude: ['dayjs'],
  },
  build: {
    target: 'es2015', // Transpiles modern syntax down to broad ES6 standard
    minify: 'terser', // Optional: safer minifier if default esbuild trips over old engines
    cssTarget: 'chrome61', // Ensures CSS properties match older browser layout engines
  }
});
