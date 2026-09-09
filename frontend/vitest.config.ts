import { defineConfig } from "vitest/config";

export default defineConfig({
  esbuild: {
    jsx: "automatic",
  },
  test: {
    projects: [
      {
        test: {
          environment: "node",
          include: ["test/unit/**/*.test.ts"],
          maxWorkers: 1,
          name: "unit",
          pool: "threads",
        },
      },
      {
        test: {
          environment: "jsdom",
          include: ["test/component/**/*.test.tsx"],
          maxWorkers: 1,
          name: "component",
          pool: "threads",
        },
      },
      {
        test: {
          environment: "node",
          include: ["test/integration/**/*.test.ts"],
          maxWorkers: 1,
          name: "integration",
          pool: "threads",
        },
      },
    ],
  },
});
