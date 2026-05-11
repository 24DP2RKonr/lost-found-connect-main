const BASE = (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://localhost:8000/api';

function getToken() {
  return localStorage.getItem('token');
}

async function request(path: string, options: RequestInit = {}) {
  const token = getToken();
  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  const res = await fetch(`${BASE}${path}`, {
    headers,
    ...options,
  });
  if (!res.ok) {
    let payload: any = null;
    try {
      payload = await res.json();
    } catch {
      // ignore
    }

    const status = res.status;
    const message =
      payload?.message ||
      (payload?.errors
        ? Object.values(payload.errors).flat().filter(Boolean)[0]
        : null) ||
      `Request failed with status ${status}`;

    const err: any = new Error(message);
    err.status = status;
    err.payload = payload;
    throw err;
  }
  return res.json();
}

export const api = {
  get: (path: string) => request(path),
  post: (path: string, body: any) => request(path, { method: 'POST', body: body instanceof FormData ? body : JSON.stringify(body) }),
  put: (path: string, body: any) => request(path, { method: 'PUT', body: body instanceof FormData ? body : JSON.stringify(body) }),
  delete: (path: string) => request(path, { method: 'DELETE' }),
};
