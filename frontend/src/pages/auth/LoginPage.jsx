import { useState } from 'react'
import { BrandIcon } from '../../components/common/BrandIcon'
import { apiRequest } from '../../services/apiClient'

const loginBenefits = [
  "Test d'orientation personnalisé",
  'Recommandations adaptées',
  'Accès aux conseillers certifiés',
  'Informations fiables et à jour',
]

function AuthIcon({ name, className = 'h-5 w-5' }) {
  const common = {
    className,
    fill: 'none',
    stroke: 'currentColor',
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    strokeWidth: 2,
    viewBox: '0 0 24 24',
    'aria-hidden': true,
  }

  const icons = {
    at: (
      <svg {...common}>
        <circle cx="12" cy="12" r="4" />
        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8" />
      </svg>
    ),
    check: (
      <svg {...common}>
        <path d="m5 12 4 4L19 6" />
      </svg>
    ),
    eye: (
      <svg {...common}>
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
        <circle cx="12" cy="12" r="3" />
      </svg>
    ),
    lock: (
      <svg {...common}>
        <rect height="11" rx="2" width="16" x="4" y="10" />
        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
      </svg>
    ),
    shield: (
      <svg {...common}>
        <path d="M12 3 20 6v6c0 5-3.2 8-8 10-4.8-2-8-5-8-10V6l8-3Z" />
        <path d="m9 12 2 2 4-5" />
      </svg>
    ),
    sparkle: (
      <svg {...common}>
        <path d="M12 3v5M12 16v5M3 12h5M16 12h5M5.6 5.6l3.5 3.5M14.9 14.9l3.5 3.5M18.4 5.6l-3.5 3.5M9.1 14.9l-3.5 3.5" />
      </svg>
    ),
    user: (
      <svg {...common}>
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4 21a8 8 0 0 1 16 0" />
      </svg>
    ),
  }

  return icons[name] ?? null
}

function TextField({ icon, label, name, placeholder, type = 'text', autoComplete }) {
  return (
    <label className="block">
      <span className="text-sm font-black text-[#06255a]">{label}</span>
      <span className="mt-2 flex min-h-12 items-center gap-3 rounded-md border border-slate-200 bg-white px-4 text-slate-400 focus-within:border-[#0b58bd] focus-within:ring-4 focus-within:ring-blue-100">
        <AuthIcon className="h-5 w-5 shrink-0" name={icon} />
        <input
          autoComplete={autoComplete}
          className="w-full border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400"
          name={name}
          placeholder={placeholder}
          type={type}
        />
        {type === 'password' && (
          <button className="text-slate-400" type="button" aria-label="Afficher le mot de passe">
            <AuthIcon className="h-4 w-4" name="eye" />
          </button>
        )}
      </span>
    </label>
  )
}

function GraduationIllustration() {
  return (
    <div className="relative mx-auto mt-12 h-72 max-w-sm">
      <div className="absolute inset-0 rounded-full bg-blue-50" />
      <div className="absolute bottom-8 left-16 h-20 w-44 -rotate-6 rounded-lg border-4 border-[#3f8c33] bg-white shadow-xl" />
      <div className="absolute bottom-16 left-20 h-20 w-44 -rotate-6 rounded-lg bg-[#0b58bd] shadow-xl" />
      <div className="absolute bottom-28 left-28 h-16 w-36 rotate-[-8deg] bg-[#06265c] shadow-lg [clip-path:polygon(0_35%,100%_0,100%_50%,0_85%)]" />
      <div className="absolute bottom-24 left-44 h-2 w-2 rounded-full bg-[#d7a328]" />
      <div className="absolute bottom-12 left-48 h-24 w-px bg-[#d7a328]" />
      <div className="absolute bottom-9 left-47 h-4 w-4 rounded-full bg-[#d7a328]" />
      <div className="absolute bottom-6 right-12 h-24 w-14 rounded-b-2xl rounded-t-full bg-slate-200" />
      <div className="absolute bottom-28 right-17 h-24 w-1 rounded-full bg-[#2fa34a]" />
      <div className="absolute bottom-40 right-12 h-14 w-10 rounded-full border-l-4 border-[#2fa34a]" />
      <div className="absolute bottom-34 right-20 h-12 w-8 rounded-full border-r-4 border-[#2fa34a]" />
    </div>
  )
}

/**
 * Page de connexion front-end.
 * Les attributs name restent alignes sur la table users: email, password et remember_token.
 */
export function LoginPage() {
  const [status, setStatus] = useState({ type: '', message: '' })
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit(event) {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus({ type: '', message: '' })

    const formData = new FormData(event.currentTarget)

    try {
      const payload = await apiRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({
          email: formData.get('email'),
          password: formData.get('password'),
          device_name: 'web',
        }),
      })

      window.localStorage.setItem('university_key_token', payload.token)
      setStatus({ type: 'success', message: payload.message })
      window.location.hash = 'home'
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section className="bg-gradient-to-br from-white via-white to-blue-50 px-4 py-14 sm:px-6 lg:px-8">
      <div className="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-[0.85fr_1.15fr]">
        <aside className="hidden lg:block">
          <p className="text-xs font-black uppercase tracking-normal text-[#073f8f]">Bienvenue</p>
          <h1 className="mt-5 max-w-md text-4xl font-black leading-tight text-[#061d49]">
            Connectez-vous à votre compte
          </h1>
          <p className="mt-6 max-w-sm text-base leading-8 text-slate-600">
            Accédez à votre espace personnel et continuez votre parcours vers la réussite.
          </p>
          <ul className="mt-10 space-y-5">
            {loginBenefits.map((benefit) => (
              <li className="flex items-center gap-4 text-sm font-bold text-slate-600" key={benefit}>
                <span className="grid h-7 w-7 place-items-center rounded-md bg-blue-50 text-[#073f8f]">
                  <AuthIcon className="h-4 w-4" name="sparkle" />
                </span>
                {benefit}
              </li>
            ))}
          </ul>
          <GraduationIllustration />
        </aside>

        <div className="mx-auto w-full max-w-xl">
          <form className="rounded-lg border border-slate-100 bg-white p-7 shadow-2xl shadow-blue-950/10 sm:p-10" data-table="users" onSubmit={handleSubmit}>
            <h2 className="text-3xl font-black text-[#061d49]">Connexion</h2>
            <p className="mt-3 text-sm text-slate-500">Entrez vos identifiants pour vous connecter</p>

            <div className="mt-8 space-y-6">
              <TextField
                autoComplete="username"
                icon="user"
                label="Adresse e-mail ou numéro de téléphone"
                name="email"
                placeholder="exemple@email.com ou 6XXXXXXX"
              />
              <TextField
                autoComplete="current-password"
                icon="lock"
                label="Mot de passe"
                name="password"
                placeholder="Votre mot de passe"
                type="password"
              />
            </div>

            {status.message && (
              <p className={`mt-5 rounded-md px-4 py-3 text-sm font-bold ${status.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
                {status.message}
              </p>
            )}

            <div className="mt-5 flex items-center justify-between gap-4 text-sm">
              <label className="flex items-center gap-2 text-slate-600">
                <input className="h-4 w-4 rounded border-slate-300 text-[#073f8f]" name="remember_token" type="checkbox" />
                Se souvenir de moi
              </label>
              <a className="focus-ring rounded-sm font-black text-[#073f8f]" href="#mot-de-passe-oublie">
                Mot de passe oublié ?
              </a>
            </div>

            <button className="focus-ring mt-7 min-h-12 w-full rounded-md bg-[#073f8f] px-5 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-[#052f6f] disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting} type="submit">
              {isSubmitting ? 'Connexion...' : 'Se connecter'}
            </button>

            <div className="my-8 flex items-center gap-4 text-xs text-slate-400">
              <span className="h-px flex-1 bg-slate-200" />
              ou continuer avec
              <span className="h-px flex-1 bg-slate-200" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <button className="focus-ring inline-flex min-h-11 items-center justify-center gap-3 rounded-md border border-slate-200 bg-white text-sm font-black text-slate-700 hover:bg-slate-50" type="button">
                <BrandIcon name="google" />
                Google
              </button>
              <button className="focus-ring inline-flex min-h-11 items-center justify-center gap-3 rounded-md border border-slate-200 bg-white text-sm font-black text-slate-700 hover:bg-slate-50" type="button">
                <BrandIcon name="facebook" />
                Facebook
              </button>
            </div>

            <p className="mt-8 text-center text-sm text-slate-500">
              Vous n'avez pas de compte ?{' '}
              <a className="focus-ring rounded-sm font-black text-[#073f8f]" href="#inscription">
                S'inscrire
              </a>
            </p>
          </form>

          <div className="mt-8 flex gap-5 rounded-lg bg-blue-50 p-6 text-[#073f8f]">
            <AuthIcon className="h-8 w-8 shrink-0" name="shield" />
            <div>
              <h3 className="font-black">Vos données sont sécurisées</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">
                University Key utilise un chiffrement avancé pour protéger vos informations personnelles.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
