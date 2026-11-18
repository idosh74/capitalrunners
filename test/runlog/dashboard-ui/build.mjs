import { build } from 'esbuild';

await build({
  entryPoints: ['src/main.jsx'],
  bundle: true,
  minify: true,
  format: 'esm',
  target: ['es2020'],
  outfile: 'dist/dashboard.js',
  define: { 'process.env.NODE_ENV': '"production"' },
});
