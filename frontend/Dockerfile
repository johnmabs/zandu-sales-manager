FROM node:24-bookworm-slim

ENV PNPM_HOME=/pnpm \
    PATH=/pnpm:$PATH

WORKDIR /workspace

RUN corepack enable \
    && corepack prepare pnpm@11.25.0 --activate

COPY . .

RUN pnpm install --filter @zandu/admin... --frozen-lockfile --network-concurrency=4 --fetch-timeout=120000

EXPOSE 3000

CMD ["pnpm", "--filter", "@zandu/admin", "dev", "--hostname", "0.0.0.0", "--port", "3000"]
