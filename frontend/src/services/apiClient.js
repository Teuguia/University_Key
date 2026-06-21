const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1'

/**
 * Appelle l'API Laravel avec les en-tetes communs et le jeton JWT si disponible.
 */
export async function apiRequest(path, options = {}) {
  // Le token est stocke apres login/register et reutilise sur les routes protegees.
  const token = window.localStorage.getItem('university_key_token')
  const headers = new Headers(options.headers)
  // FormData laisse le navigateur poser lui-meme la boundary multipart.
  const isFormData = options.body instanceof FormData

  headers.set('Accept', 'application/json')
  // Prepare la localisation des reponses backend et messages de validation.
  headers.set('Accept-Language', window.localStorage.getItem('university_key_language') || 'fr')

  if (!isFormData) {
    headers.set('Content-Type', 'application/json')
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  // Certaines routes peuvent repondre sans JSON; on garde alors un objet vide.
  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    // Normalise les erreurs Laravel pour les pages React.
    const error = new Error(payload.message ?? 'La requete API a echoue.')
    error.errors = payload.errors ?? {}
    throw error
  }

  return payload
}
