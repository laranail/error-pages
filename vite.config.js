import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

// Dev-only. Builds the shipped, committed bundle into public/assets with a
// by-type folder structure (css/, js/, img/) and stable, unhashed names that the
// package links (or inlines for the standalone export). NOT laravel-vite-plugin
// (that is for @vite-linked app assets — the opposite of what this package does).
export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    outDir: "public/assets",
    emptyOutDir: true,
    cssCodeSplit: false,
    minify: "esbuild",
    rollupOptions: {
      // The JS entry `import`s the SCSS, so one build emits both files.
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
});
