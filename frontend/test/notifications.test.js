import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

import { failurePresentation, NotificationCenter } from "../packages/notifications/src/index.ts";

const reactNotificationsUrl = new URL("../packages/notifications/src/react.tsx", import.meta.url);

test("notification center manages dismissible transient feedback", () => {
  const notifications = new NotificationCenter();
  const id = notifications.notify({ message: "Produit ajouté", tone: "success" });

  assert.deepEqual(notifications.getState(), [{ id, message: "Produit ajouté", tone: "success" }]);
  notifications.dismiss(id);
  assert.deepEqual(notifications.getState(), []);
});

test("critical business failures remain actionable page errors rather than toasts", () => {
  assert.equal(failurePresentation({ critical: true }), "PAGE_ERROR");
  assert.equal(failurePresentation({ critical: false }), "INLINE_ERROR");
});

test("notification viewport and confirmation require accessible dismissal and business impact", async () => {
  const components = await readFile(reactNotificationsUrl, "utf8");

  assert.match(components, /aria-label="Notifications"/);
  assert.match(components, /aria-label="Fermer la notification"/);
  assert.match(components, /impact: string/);
  assert.match(components, /<Dialog/);
});
