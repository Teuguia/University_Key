// Commentaire d'intention: affiche et traite le formulaire de connexion.

import { useState } from 'react'
import { SocialAuthRolePicker } from '../../components/auth/SocialAuthRolePicker'
import { apiRequest } from '../../services/apiClient'

// Icones SVG locales pour eviter une dependance supplementaire sur l'ecran d'authentification.
function AuthIcon({ name, className = 'h-5 w-5' }) {
  // Base commune: toutes les icones gardent la meme epaisseur et le meme style.
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

  // Le dictionnaire permet d'appeler une icone par son nom depuis les champs et les cartes.
  const icons = {
    eye: (
      <svg {...common}>
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
        <circle cx="12" cy="12" r="3" />
      </svg>
    ),
    eyeOff: (
      <svg {...common}>
        <path d="M3 3l18 18" />
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
        <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16.8 16.8 0 0 1-3.1 4.2" />
        <path d="M6.6 6.7C3.6 8.7 2 12 2 12s3.5 7 10 7a10.7 10.7 0 0 0 4.1-.8" />
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

// Champ de formulaire reutilisable avec icone, label localise et bouton visuel pour les mots de passe.
function TextField({ icon, label, name, placeholder, type = 'text', autoComplete, showPasswordLabel }) {
  const [isPasswordVisible, setIsPasswordVisible] = useState(false)
  const isPasswordField = type === 'password'
  const inputType = isPasswordField && isPasswordVisible ? 'text' : type

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
          type={inputType}
        />
        {isPasswordField && (
          <button
            aria-label={isPasswordVisible ? 'Masquer le mot de passe' : showPasswordLabel}
            aria-pressed={isPasswordVisible}
            className="text-slate-400 hover:text-[#073f8f]"
            onClick={() => setIsPasswordVisible((visible) => !visible)}
            type="button"
          >
            <AuthIcon className="h-4 w-4" name={isPasswordVisible ? 'eyeOff' : 'eye'} />
          </button>
        )}
      </span>
    </label>
  )
}

// Illustration decorative affichee uniquement sur desktop pour equilibrer la page de connexion.
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
 */
export function LoginPage({ labels }) {
  // status pilote le message utilisateur; isSubmitting bloque le double envoi du formulaire.
  const [status, setStatus] = useState({ type: '', message: '' })
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [socialRole, setSocialRole] = useState('etudiant')

  async function handleSubmit(event) {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus({ type: '', message: '' })

    const formData = new FormData(event.currentTarget)

    try {
      // Le backend accepte ici un nom, un e-mail ou un telephone dans le champ "email".
      const payload = await apiRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({
          email: formData.get('email'),
          password: formData.get('password'),
          device_name: 'web',
        }),
      })

      // Le token Sanctum est conserve cote navigateur pour les prochains appels API proteges.
      window.localStorage.setItem('university_key_token', payload.token)
      setStatus({ type: 'success', message: payload.message })
      // Chaque role est redirige vers son espace: admin, etudiant ou accueil par defaut.
      window.location.hash = payload.user?.role === 'admin' ? 'admin' : payload.user?.role === 'conseiller' ? 'conseiller' : payload.user?.role === 'etudiant' ? 'dashboard' : 'home'
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    } finally {
      setIsSubmitting(false)
    }
  }

  function handleSocialProvider(provider, selectedRole) {
    window.localStorage.setItem('university_key_social_role', selectedRole)
    setStatus({
      type: 'info',
      message: (labels.socialUnavailable ?? 'Connexion sociale a configurer pour le role :role.').replace(':provider', provider).replace(':role', selectedRole === 'conseiller' ? labels.counselor : labels.student),
    })
  }

  function statusClass(type) {
    if (type === 'success') {
      return 'bg-emerald-50 text-emerald-700'
    }

    if (type === 'info') {
      return 'bg-blue-50 text-[#073f8f]'
    }

    return 'bg-red-50 text-red-700'
  }

  return (
    <section className="bg-gradient-to-br from-white via-white to-blue-50 px-4 py-14 sm:px-6 lg:px-8">
      <div className="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-[0.85fr_1.15fr]">
        {/* Colonne editoriale: benefices de la plateforme, masquee sur mobile pour garder le formulaire prioritaire. */}
        <aside className="hidden lg:block">
          <p className="text-xs font-black uppercase tracking-normal text-[#073f8f]">{labels.loginEyebrow}</p>
          <h1 className="mt-5 max-w-md text-4xl font-black leading-tight text-[#061d49]">{labels.loginTitle}</h1>
          <p className="mt-6 max-w-sm text-base leading-8 text-slate-600">{labels.loginText}</p>
          <ul className="mt-10 space-y-5">
            {labels.loginBenefits.map((benefit) => (
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
          {/* Formulaire principal: les libelles viennent de copy.js pour suivre le changement de langue global. */}
          <form className="rounded-lg border border-slate-100 bg-white p-7 shadow-2xl shadow-blue-950/10 sm:p-10" data-table="users" onSubmit={handleSubmit}>
            <h2 className="text-3xl font-black text-[#061d49]">{labels.loginFormTitle}</h2>
            <p className="mt-3 text-sm text-slate-500">{labels.loginFormText}</p>

            <div className="mt-8 space-y-6">
              <TextField autoComplete="username" icon="user" label={labels.identifier} name="email" placeholder={labels.identifierPlaceholder} />
              <TextField
                autoComplete="current-password"
                icon="lock"
                label={labels.password}
                name="password"
                placeholder={labels.passwordPlaceholder}
                showPasswordLabel={labels.showPassword}
                type="password"
              />
            </div>

            {status.message && (
              // Message de retour API: succes apres connexion ou erreur de validation/authentification.
              <p className={`mt-5 rounded-md px-4 py-3 text-sm font-bold ${statusClass(status.type)}`}>
                {status.message}
              </p>
            )}

            <div className="mt-5 flex items-center justify-between gap-4 text-sm">
              <label className="flex items-center gap-2 text-slate-600">
                <input className="h-4 w-4 rounded border-slate-300 text-[#073f8f]" name="remember_token" type="checkbox" />
                {labels.rememberMe}
              </label>
              <a className="focus-ring rounded-sm font-black text-[#073f8f]" href="#mot-de-passe-oublie">
                {labels.forgotPassword}
              </a>
            </div>

            <button className="focus-ring mt-7 min-h-12 w-full rounded-md bg-[#073f8f] px-5 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-[#052f6f] disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting} type="submit">
              {isSubmitting ? labels.loginLoading : labels.loginButton}
            </button>

            <div className="my-8 flex items-center gap-4 text-xs text-slate-400">
              <span className="h-px flex-1 bg-slate-200" />
              {labels.divider}
              <span className="h-px flex-1 bg-slate-200" />
            </div>

            <SocialAuthRolePicker labels={labels} onProviderSelect={handleSocialProvider} onRoleChange={setSocialRole} role={socialRole} />

            <p className="mt-8 text-center text-sm text-slate-500">
              {labels.noAccount}{' '}
              <a className="focus-ring rounded-sm font-black text-[#073f8f]" href="#inscription">
                {labels.signupLink}
              </a>
            </p>
          </form>

          <div className="mt-8 flex gap-5 rounded-lg bg-blue-50 p-6 text-[#073f8f]">
            <AuthIcon className="h-8 w-8 shrink-0" name="shield" />
            <div>
              <h3 className="font-black">{labels.secureTitle}</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">{labels.secureText}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
