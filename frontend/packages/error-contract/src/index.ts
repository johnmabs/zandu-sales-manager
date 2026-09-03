export type FieldErrors = Readonly<Record<string, readonly string[]>>;

export type ApiError =
  | {
      correlationId?: string;
      kind: "network";
      message?: string;
    }
  | {
      code?: string;
      correlationId?: string;
      fieldErrors?: FieldErrors;
      kind: "response";
      message?: string;
      status: number;
    };

export type UiErrorAction = "retry" | "sign-in";

export type UiError = {
  action?: UiErrorAction;
  correlationId?: string;
  fieldErrors?: FieldErrors;
  message: string;
  retryable: boolean;
  title: string;
};

export type ErrorMapping = Pick<UiError, "action" | "message" | "retryable" | "title">;
export type ErrorMappings = Readonly<Record<string, ErrorMapping>>;

type ErrorCategory =
  | "authentication"
  | "authorization"
  | "business-conflict"
  | "idempotency-conflict"
  | "network"
  | "not-found"
  | "server"
  | "validation";

const categoryMappings: Readonly<Record<ErrorCategory, ErrorMapping>> = {
  authentication: {
    action: "sign-in",
    message: "Votre session a expiré. Connectez-vous pour continuer.",
    retryable: false,
    title: "Connexion requise",
  },
  authorization: {
    message: "Vous n’êtes pas autorisé à effectuer cette action.",
    retryable: false,
    title: "Action non autorisée",
  },
  "business-conflict": {
    message: "Cette action ne peut pas être effectuée dans l’état actuel.",
    retryable: false,
    title: "Action impossible",
  },
  "idempotency-conflict": {
    message: "Cette demande a déjà été traitée avec des informations différentes.",
    retryable: false,
    title: "Demande en conflit",
  },
  network: {
    action: "retry",
    message: "Impossible de joindre le serveur. Vérifiez votre connexion puis réessayez.",
    retryable: true,
    title: "Connexion indisponible",
  },
  "not-found": {
    message: "La ressource demandée est introuvable ou n’est plus disponible.",
    retryable: false,
    title: "Introuvable",
  },
  server: {
    action: "retry",
    message: "Le service rencontre un problème. Réessayez plus tard.",
    retryable: true,
    title: "Erreur du service",
  },
  validation: {
    message: "Certaines informations sont invalides. Corrigez les champs indiqués.",
    retryable: false,
    title: "Informations à corriger",
  },
};

const defaultCodeMappings: ErrorMappings = {
  INSUFFICIENT_STOCK: {
    message: "Stock insuffisant pour finaliser la vente.",
    retryable: false,
    title: "Stock insuffisant",
  },
  TRANSFER_INSUFFICIENT_STOCK: {
    message: "Stock insuffisant pour expédier le transfert.",
    retryable: false,
    title: "Stock insuffisant",
  },
};

const validationCodes = new Set(["INVALID_REQUEST", "VALIDATION_ERROR", "VALIDATION_FAILED"]);

function categoryFor(error: ApiError): ErrorCategory {
  if (error.kind === "network") {
    return "network";
  }

  if (error.code === "IDEMPOTENCY_CONFLICT") {
    return "idempotency-conflict";
  }

  if (error.status === 401) {
    return "authentication";
  }

  if (error.status === 403) {
    return "authorization";
  }

  if (error.status === 404) {
    return "not-found";
  }

  if (
    error.status === 400 ||
    error.fieldErrors !== undefined ||
    (error.code !== undefined && validationCodes.has(error.code))
  ) {
    return "validation";
  }

  if (error.status === 409 || error.status === 422) {
    return "business-conflict";
  }

  return "server";
}

export class ErrorMapper {
  private readonly codeMappings: ErrorMappings;

  constructor(codeMappings: ErrorMappings = {}) {
    this.codeMappings = codeMappings;
  }

  map(error: ApiError, featureMappings: ErrorMappings = {}): UiError {
    const code = error.kind === "response" ? error.code : undefined;
    const mapping =
      (code === undefined ? undefined : featureMappings[code]) ??
      (code === undefined ? undefined : this.codeMappings[code]) ??
      (code === undefined ? undefined : defaultCodeMappings[code]) ??
      categoryMappings[categoryFor(error)];

    return {
      ...mapping,
      ...(error.kind === "response" && error.fieldErrors !== undefined
        ? { fieldErrors: error.fieldErrors }
        : {}),
      ...(error.correlationId === undefined ? {} : { correlationId: error.correlationId }),
    };
  }
}
