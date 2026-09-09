import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import { ApiClient } from "../../packages/api-client/src/index";

const server = setupServer(
  http.get("https://api.zandu.test/api/stores", ({ request }) => {
    expect(request.headers.get("authorization")).toBe("Bearer access-token");
    expect(request.headers.get("x-correlation-id")).toBe("correlation-id");

    return HttpResponse.json({ items: [{ id: "store-1", name: "Main store" }] });
  }),
);

beforeAll(() => server.listen({ onUnhandledRequest: "error" }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

describe("ApiClient at the mocked API boundary", () => {
  it("sends authenticated requests and decodes the server projection", async () => {
    const client = new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      generateCorrelationId: () => "correlation-id",
      session: {
        getAccessToken: () => "access-token",
        refreshAndRetry: async (retry) => retry("refreshed-access-token"),
      },
    });

    await expect(client.request({ method: "GET", path: "stores" })).resolves.toMatchObject({
      data: { items: [{ id: "store-1", name: "Main store" }] },
      status: 200,
    });
  });

  it("keeps backend validation details at the boundary", async () => {
    server.use(
      http.post("https://api.zandu.test/api/stores", () =>
        HttpResponse.json(
          { code: "VALIDATION_ERROR", fieldErrors: { name: ["Required"] } },
          { status: 422 },
        ),
      ),
    );
    const client = new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
    });

    await expect(
      client.request({ body: {}, method: "POST", path: "stores" }),
    ).rejects.toMatchObject({
      apiError: { code: "VALIDATION_ERROR", fieldErrors: { name: ["Required"] }, status: 422 },
    });
  });
});
