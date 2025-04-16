import { defineConfig } from "vite";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  base: "/static/",
  build: {
    outDir: resolve("./dist"),
    manifest: "manifest.json",
    rollupOptions: {
      input: {
        main: resolve("./src/main.js"),
      },
    },
  },
  plugins: [tailwindcss()],
});
