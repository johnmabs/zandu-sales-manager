import js from "@eslint/js";
import prettier from "eslint-config-prettier";
import importPlugin from "eslint-plugin-import";
import globals from "globals";
import tseslint from "typescript-eslint";

const applicationBoundary = (targetApplication, forbiddenApplication) => ({
  "import/no-restricted-paths": [
    "error",
    {
      basePath: process.cwd(),
      zones: [
        {
          from: `./apps/${forbiddenApplication}`,
          message:
            "Applications must not import another application. Extract genuinely shared code into a package.",
          target: `./apps/${targetApplication}`,
        },
      ],
    },
  ],
  "no-restricted-imports": [
    "error",
    {
      paths: [
        {
          message:
            "Applications must not import another application. Extract genuinely shared code into a package.",
          name: `@zandu/${forbiddenApplication}`,
        },
      ],
    },
  ],
});

export default tseslint.config(
  {
    ignores: [
      "**/.next/**",
      "**/dist/**",
      "**/node_modules/**",
      "**/src-tauri/target/**",
      "apps/admin/next-env.d.ts",
      "pnpm-lock.yaml",
    ],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ["**/*.{js,mjs,cjs,ts,tsx}"],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
    plugins: {
      import: importPlugin,
    },
    settings: {
      "import/resolver": {
        node: {
          extensions: [".js", ".mjs", ".cjs", ".ts", ".tsx"],
        },
      },
    },
    rules: {
      "@typescript-eslint/consistent-type-assertions": ["error", { assertionStyle: "never" }],
      "@typescript-eslint/consistent-type-imports": [
        "error",
        { fixStyle: "separate-type-imports", prefer: "type-imports" },
      ],
      "@typescript-eslint/no-explicit-any": "error",
      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
        },
      ],
      "import/no-duplicates": "error",
      "import/order": [
        "error",
        {
          alphabetize: { caseInsensitive: true, order: "asc" },
          groups: ["builtin", "external", "internal", "parent", "sibling", "index", "type"],
          "newlines-between": "always",
        },
      ],
      "no-unused-vars": "off",
    },
  },
  {
    files: ["apps/admin/**/*.{js,ts,tsx}"],
    rules: {
      ...applicationBoundary("admin", "pos"),
    },
  },
  {
    files: ["apps/pos/**/*.{js,ts,tsx}"],
    rules: {
      ...applicationBoundary("pos", "admin"),
    },
  },
  {
    files: ["packages/**/*.{js,ts,tsx}"],
    rules: {
      "import/no-restricted-paths": [
        "error",
        {
          basePath: process.cwd(),
          zones: [
            {
              from: "./apps",
              message: "Shared packages must not depend on applications.",
              target: "./packages",
            },
          ],
        },
      ],
      "no-restricted-imports": [
        "error",
        {
          patterns: [
            {
              group: ["**/apps/**", "@zandu/admin", "@zandu/pos"],
              message: "Shared packages must not depend on applications.",
            },
          ],
        },
      ],
    },
  },
  prettier,
);
