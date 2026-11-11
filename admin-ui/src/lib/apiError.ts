type PlanErrorsBody = {
  error?: string;
  errors?: string[];
  plan?: {
    compatibility?: { errors?: string[] };
    errors?: string[];
  };
  compatibility?: { errors?: string[] };
};

export async function formatApiError(response: Response, fallback: string): Promise<string> {
  let detail = fallback;
  try {
    const body = (await response.json()) as PlanErrorsBody;
    const parts: string[] = [];
    if (typeof body.error === 'string' && body.error !== '') {
      parts.push(body.error);
    }
    if (Array.isArray(body.errors)) {
      for (const row of body.errors) {
        if (typeof row === 'string' && row !== '') {
          parts.push(row);
        }
      }
    }
    const planCompat = body.plan?.compatibility?.errors ?? body.compatibility?.errors;
    if (Array.isArray(planCompat)) {
      for (const row of planCompat) {
        if (typeof row === 'string' && row !== '') {
          parts.push(row);
        }
      }
    }
    const planErrors = body.plan?.errors;
    if (Array.isArray(planErrors)) {
      for (const row of planErrors) {
        if (typeof row === 'string' && row !== '') {
          parts.push(row);
        }
      }
    }
    if (parts.length > 0) {
      detail = parts.join(' · ');
    }
  } catch {
    // keep fallback
  }

  return detail;
}
