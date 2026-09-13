import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const directory = new URL("../src/features/access/", import.meta.url);

test("sensitive Access actions name their target and consequence", async () => {
  const files = await Promise.all(
    [
      "invitations/CancelInvitationDialog.tsx",
      "members/RemoveRoleDialog.tsx",
      "members/SuspendMemberDialog.tsx",
      "members/RevokeMemberDialog.tsx",
    ].map((name) => readFile(new URL(name, directory), "utf8")),
  );
  for (const file of files) {
    assert.match(file, /descriptionId/);
    assert.match(file, /Confirmer/);
  }
  assert.match(files[0], /email/);
  assert.match(files[1], /assignment\.roleId/);
  assert.match(files[2], /memberLabel/);
  assert.match(files[3], /terminale/);
});
