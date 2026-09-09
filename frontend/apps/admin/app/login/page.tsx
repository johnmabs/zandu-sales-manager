"use client";

import { Button, Input } from "@zandu/ui";
import { useRouter } from "next/navigation";
import { useState } from "react";

import { useAdminRuntime } from "../../src/runtime/AdminRuntime";

export default function LoginPage() {
  const runtime = useAdminRuntime();
  const router = useRouter();
  const [error, setError] = useState<string>();

  return (
    <main className="zandu-login">
      <form
        className="zandu-login__form"
        onSubmit={(event) => {
          event.preventDefault();
          setError(undefined);
          const form = new FormData(event.currentTarget);
          void runtime
            .login({
              email: String(form.get("email") ?? ""),
              password: String(form.get("password") ?? ""),
            })
            .then(() => router.replace("/app"))
            .catch(() => setError("Adresse e-mail ou mot de passe incorrect."));
        }}
      >
        <h1>Connexion</h1>
        {runtime.configurationError === undefined ? null : (
          <p role="alert">{runtime.configurationError}</p>
        )}
        {error === undefined ? null : <p role="alert">{error}</p>}
        <label>
          Adresse e-mail
          <Input autoComplete="username" name="email" required type="email" />
        </label>
        <label>
          Mot de passe
          <Input autoComplete="current-password" name="password" required type="password" />
        </label>
        <Button
          disabled={
            runtime.authState.status === "AUTHENTICATING" ||
            runtime.configurationError !== undefined
          }
          type="submit"
        >
          {runtime.authState.status === "AUTHENTICATING" ? "Connexion…" : "Se connecter"}
        </Button>
      </form>
    </main>
  );
}
