import { MemberDetailsPage } from "../members/MemberDetailsPage";

export function MemberDetailsRoute({ membershipId }: Readonly<{ membershipId: string }>) {
  return <MemberDetailsPage membershipId={membershipId} />;
}
