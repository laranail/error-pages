import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

// Dev-only. Builds the shipped, committed bundle into public/assets with a
// by-type folder structure (css/, js/, img/) and stable, unhashed names that the
// package links (or inlines for the standalone export). One strictly-SCSS entry:
// the JS `import`s the SCSS, which pulls in Tailwind 4 (preflight + utilities).
// NOT laravel-vite-plugin (that is for @vite-linked app assets — the opposite of
// what this package does), and publicDir is disabled since we write INTO public/.
//
// `vite build` (default) minifies the shipped bundle; `vite build --mode pretty`
// emits un-minified CSS/JS so `npm run build:pretty` can format it for inspection.
export default defineConfig(({ mode }) => {
  const pretty = mode === "pretty";

  return {
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
      outDir: "public/assets",
      emptyOutDir: true,
      cssCodeSplit: false,
      minify: pretty ? false : "esbuild",
      cssMinify: !pretty,
      rollupOptions: {
        input: "resources/assets/scripts/error-pages.js",
        output: {
          entryFileNames: "js/error-pages.js",
          assetFileNames: (asset) => {
            const name = asset.names?.[0] ?? asset.name ?? "";
            if (name.endsWith(".css")) return "css/error-pages.css";
            if (/\.(png|jpe?g|gif|svg|webp|ico|avif)$/i.test(name)) return "img/[name][extname]";
            return "[name][extname]";
          },
        },
      },
    },
  };
});
