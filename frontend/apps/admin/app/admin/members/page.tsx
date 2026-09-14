import { redirect } from "next/navigation";

/** Compatibility route kept for existing bookmarks while Access owns its navigation. */
export default function MembersPage(): never {
  redirect("/admin/access/members");
}
