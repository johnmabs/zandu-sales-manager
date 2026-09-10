"use client";

import { ApiContractError, ApiRequestError } from "@zandu/api-client";
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
            .catch((loginError: unknown) => setError(loginErrorMessage(loginError)));
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

function loginErrorMessage(error: unknown): string {
  if (error instanceof ApiRequestError) {
    if (error.apiError.kind === "network") {
      return "L’API est inaccessible. Vérifiez que Symfony est démarré et ouvrez l’Admin avec http://localhost:3000.";
    }

    if (error.apiError.status === 401) {
      return "Adresse e-mail ou mot de passe incorrect.";
    }

    if (error.apiError.status === 429) {
      return "Trop de tentatives de connexion. Réessayez dans une minute.";
    }

    return error.apiError.message ?? `La connexion a échoué (HTTP ${error.apiError.status}).`;
  }

  if (error instanceof ApiContractError) {
    return "La réponse de l’API est incompatible avec l’Admin.";
  }

  return "La connexion a échoué pour une raison inattendue.";
}
