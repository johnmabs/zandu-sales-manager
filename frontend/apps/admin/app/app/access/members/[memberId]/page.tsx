import { MemberDetailsRoute } from "../../../../../src/features/access";

export default async function MemberDetailsPage({
  params,
}: Readonly<{ params: Promise<{ memberId: string }> }>) {
  const { memberId } = await params;

  return <MemberDetailsRoute membershipId={memberId} />;
}
