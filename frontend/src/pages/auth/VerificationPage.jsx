// Commentaire d'intention: gere la double verification email/telephone avant activation du compte.

import { useState } from 'react'
import { apiRequest } from '../../services/apiClient'

/**
 * Finalise l'inscription sans exposer de token tant que les deux canaux ne
 * sont pas verifies. L'identifiant est seulement pre-rempli, jamais secret.
 */
export function VerificationPage({ labels }) {
  const [identifier, setIdentifier] = useState(() => window.localStorage.getItem('university_key_verification_identifier') || '')
  const [type, setType] = useState('email')
  const [code, setCode] = useState('')
  const [debugCodes] = useState(() => {
    try {
      return JSON.parse(window.localStorage.getItem('university_key_debug_verification_codes') || 'null')
    } catch {
      return null
    }
  })
  const [status, setStatus] = useState({ type: '', message: '' })
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function verify(event) {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus({ type: '', message: '' })

    try {
      const payload = await apiRequest('/auth/verification/verify', {
        method: 'POST',
        body: JSON.stringify({ identifiant: identifier, type, code, device_name: 'web' }),
      })

      if (payload.token) {
        window.localStorage.setItem('university_key_token', payload.token)
        window.localStorage.removeItem('university_key_verification_identifier')
        window.localStorage.removeItem('university_key_debug_verification_codes')
        window.location.hash = payload.user?.role === 'etudiant' ? 'dashboard' : 'home'
        return
      }

      setCode('')
      setStatus({ type: 'success', message: payload.message })
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    } finally {
      setIsSubmitting(false)
    }
  }

  async function resend() {
    setIsSubmitting(true)
    setStatus({ type: '', message: '' })

    try {
      const payload = await apiRequest('/auth/verification/resend', {
        method: 'POST',
        body: JSON.stringify({ identifiant: identifier, type }),
      })
      setStatus({ type: 'success', message: payload.message })
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section className="bg-gradient-to-br from-white via-white to-blue-50 px-4 py-14 sm:px-6 lg:px-8">
      <form className="mx-auto max-w-lg rounded-lg border border-slate-100 bg-white p-7 shadow-2xl shadow-blue-950/10 sm:p-10" onSubmit={verify}>
        <p className="text-xs font-black uppercase tracking-normal text-[#073f8f]">{labels.verificationEyebrow}</p>
        <h1 className="mt-3 text-3xl font-black text-[#061d49]">{labels.verificationPageTitle}</h1>
        <p className="mt-3 text-sm leading-6 text-slate-600">{labels.verificationPageText}</p>

        {debugCodes && (
          <div className="mt-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
            <p>Codes de test Cloud</p>
            <p className="mt-1">E-mail : {debugCodes.email ?? 'non disponible'}</p>
            <p>Telephone : {debugCodes.telephone ?? 'non disponible'}</p>
          </div>
        )}

        <label className="mt-7 block text-sm font-black text-[#06255a]">
          {type === 'email' ? labels.email : labels.phone}
          <input className="mt-2 min-h-12 w-full rounded-md border border-slate-200 px-4 text-sm text-slate-900 outline-none focus:border-[#0b58bd] focus:ring-4 focus:ring-blue-100" onChange={(event) => setIdentifier(event.target.value)} required type={type === 'email' ? 'email' : 'tel'} value={identifier} />
        </label>

        <fieldset className="mt-5">
          <legend className="text-sm font-black text-[#06255a]">{labels.verificationChannel}</legend>
          <div className="mt-2 grid grid-cols-2 gap-3">
            {['email', 'telephone'].map((channel) => (
              <label className={`cursor-pointer rounded-md border px-4 py-3 text-center text-sm font-black ${type === channel ? 'border-[#073f8f] bg-blue-50 text-[#073f8f]' : 'border-slate-200 text-slate-600'}`} key={channel}>
                <input checked={type === channel} className="sr-only" name="channel" onChange={() => setType(channel)} type="radio" value={channel} />
                {channel === 'email' ? labels.email : labels.phone}
              </label>
            ))}
          </div>
        </fieldset>

        <label className="mt-5 block text-sm font-black text-[#06255a]">
          {labels.verificationCode}
          <input autoComplete="one-time-code" className="mt-2 min-h-12 w-full rounded-md border border-slate-200 px-4 text-center text-lg font-black tracking-[0.45em] text-slate-900 outline-none focus:border-[#0b58bd] focus:ring-4 focus:ring-blue-100" inputMode="numeric" maxLength="6" onChange={(event) => setCode(event.target.value.replace(/\D/g, ''))} required value={code} />
        </label>

        {status.message && <p className={`mt-5 rounded-md px-4 py-3 text-sm font-bold ${status.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>{status.message}</p>}

        <button className="focus-ring mt-7 min-h-12 w-full rounded-md bg-[#073f8f] px-5 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting || code.length !== 6} type="submit">
          {isSubmitting ? labels.verificationLoading : labels.verificationButton}
        </button>
        <button className="focus-ring mt-4 min-h-11 w-full rounded-md border border-[#073f8f] px-5 text-sm font-black text-[#073f8f] disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting || !identifier} onClick={resend} type="button">
          {labels.resendCode}
        </button>
      </form>
    </section>
  )
}
