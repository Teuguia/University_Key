const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1'

/**
 * Appelle l'API Laravel avec les en-tetes communs et le jeton JWT si disponible.
 */
export async function apiRequest(path, options = {}) {
  const token = window.localStorage.getItem('university_key_token')
  const headers = new Headers(options.headers)

  headers.set('Accept', 'application/json')
  headers.set('Content-Type', 'application/json')

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    const error = new Error(payload.message ?? 'La requete API a echoue.')
    error.errors = payload.errors ?? {}
    throw error
  }

  return payload
}
