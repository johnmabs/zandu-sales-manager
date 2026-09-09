import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

import {
  cashSessionStatusDisplay,
  connectivityStatusDisplay,
  syncStatusDisplay,
} from "../src/features/terminal/operational-status.ts";

const appUrl = new URL("../src/app/App.tsx", import.meta.url);
const shellUrl = new URL("../src/features/terminal/PosShell.tsx", import.meta.url);

test("POS shell renders persistent operational information without a sidebar", async () => {
  const [app, shell] = await Promise.all([readFile(appUrl, "utf8"), readFile(shellUrl, "utf8")]);

  assert.match(app, /<h1 id="pos-title">Zandu POS<\/h1>/);
  assert.match(shell, /aria-label="État opérationnel du point de vente"/);
  assert.match(shell, /Magasin/);
  assert.match(shell, /Caisse/);
  assert.match(shell, /Caissier/);
  assert.match(shell, /Session caisse/);
  assert.doesNotMatch(shell, /<aside/);
});

test("POS operational and future connectivity states remain explicit", () => {
  assert.deepEqual(cashSessionStatusDisplay("OPEN"), { label: "Ouverte", tone: "success" });
  assert.deepEqual(connectivityStatusDisplay("UNKNOWN"), { label: "À venir", tone: "neutral" });
  assert.deepEqual(syncStatusDisplay("PENDING"), { label: "En attente", tone: "warning" });
});
