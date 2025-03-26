import { defineConfig } from 'vite';
import * as glob from 'glob';
import path from 'path';
import fs from 'fs';
import autoprefixer from 'autoprefixer';

// Collect all JS files
const jsFiles = glob
    .sync(`./src/js/*.js`, { ignore: '**/_*.js' });

// Collect all SCSS files, filtering out empty ones
const scssFiles = glob
  .sync(`./src/scss/**.scss`, { ignore: '**/_*.scss' })
  .filter((file) => fs.statSync(file).size > 0); // Only include non-empty files

export default defineConfig(({ command }) => ({
  root: path.resolve(__dirname, 'src'),
  build: {
        outDir: path.resolve(__dirname, 'resources'),
        emptyOutDir: false,
        rollupOptions: {
            input: [
                ...jsFiles.map((file) => path.resolve(file)),
                ...scssFiles.map((file) => path.resolve(file)),
            ],
            output: {
                entryFileNames: 'js/[name].min.js',
                chunkFileNames: 'js/[name].min.js',
                assetFileNames: ({ name }) => {
                    if (name && name.endsWith('.css')) {
                        return 'css/[name].min.css';
                    }
                    
                    return 'images/[name].[ext]';
                },
            },
        },
  },
  resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src/*'),
        },
  },
  css: {
    postcss: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler'
            }
        },
        plugins: [
            require('postcss-url')({
                url: 'inline',
                fallback: 'copy',
                    assetsPath: path.resolve(__dirname, '/resources/images'),
                }),
            require('postcss-nested')(),
            autoprefixer(), // Added autoprefixer here
            require('postcss-sort-media-queries')({
                sort: 'mobile-first'
            }),
        ],
    },
  },
}));