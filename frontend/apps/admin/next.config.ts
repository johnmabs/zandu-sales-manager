import type { NextConfig } from "next";

const backendOrigin = process.env.ZANDU_BACKEND_URL ?? "http://localhost:8080";

const config: NextConfig = {
  async rewrites() {
    return [
      {
        destination: `${backendOrigin}/api/:path*`,
        source: "/api/:path*",
      },
    ];
  },
};

export default config;
