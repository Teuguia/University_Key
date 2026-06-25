// Commentaire d'intention: affiche et traite le formulaire d'inscription.

import { useState } from 'react'
import { BrandIcon } from '../../components/common/BrandIcon'
import { apiRequest } from '../../services/apiClient'

const studentImage = '/images/hero-student.png'

// Icones SVG locales utilisees par les champs et les messages de la page d'inscription.
function AuthIcon({ name, className = 'h-5 w-5' }) {
  // Base commune pour garder une apparence coherente entre toutes les icones.
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

  // Dictionnaire d'icones appelees par nom depuis les composants de formulaire.
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
    mail: (
      <svg {...common}>
        <path d="M4 4h16v16H4z" />
        <path d="m4 7 8 6 8-6" />
      </svg>
    ),
    phone: (
      <svg {...common}>
        <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z" />
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
    verify: (
      <svg {...common}>
        <path d="M12 3 20 7v5c0 5-3 8-8 10-5-2-8-5-8-10V7l8-4Z" />
        <path d="m9 12 2 2 4-5" />
      </svg>
    ),
  }

  return icons[name] ?? null
}

// Champ reutilisable: il centralise le rendu label + icone + input + bouton visuel mot de passe.
function TextField({ icon, label, name, placeholder, type = 'text', autoComplete, value, onChange, showPasswordLabel }) {
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
          onChange={onChange}
          placeholder={placeholder}
          type={inputType}
          value={value}
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

/**
 * Page d'inscription front-end.
 */
export function RegisterPage({ labels, onOpenLegal }) {
  // identity permet de construire le champ cache name attendu par la table users.
  const [identity, setIdentity] = useState({ prenom: '', nom: '' })
  // Le role determine les champs supplementaires, notamment la specialite conseiller.
  const [role, setRole] = useState('etudiant')
  // status affiche le retour API; isSubmitting bloque les doubles clics.
  const [status, setStatus] = useState({ type: '', message: '' })
  const [isSubmitting, setIsSubmitting] = useState(false)
  const fullName = `${identity.prenom} ${identity.nom}`.trim()
  // On enregistre la langue active avec le profil pour personnaliser les messages futurs.
  const language = window.localStorage.getItem('university_key_language') || 'fr'

  async function handleSubmit(event) {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus({ type: '', message: '' })

    const formData = new FormData(event.currentTarget)

    try {
      // Les champs sont alignes sur RegisterRequest cote Laravel.
      const payload = await apiRequest('/auth/register', {
        method: 'POST',
        body: JSON.stringify({
          prenom: formData.get('prenom'),
          nom: formData.get('nom'),
          role: formData.get('role'),
          email: formData.get('email'),
          telephone: formData.get('telephone'),
          specialite: formData.get('specialite'),
          password: formData.get('password'),
          password_confirmation: formData.get('password_confirmation'),
          conditions_acceptees: formData.get('conditions_acceptees'),
          langue_preferee: language,
          device_name: 'web',
        }),
      })

      // Aucun token n'est delivre avant la double verification e-mail/telephone.
      window.localStorage.setItem('university_key_verification_identifier', formData.get('email'))
      setStatus({ type: 'success', message: payload.message })
      window.location.hash = 'verification'
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section className="bg-gradient-to-br from-white via-white to-blue-50 px-4 py-14 sm:px-6 lg:px-8">
      <div className="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-[0.85fr_1.15fr]">
        {/* Colonne de contexte masquee sur mobile pour laisser la priorite au formulaire. */}
        <aside className="hidden lg:block">
          <p className="text-xs font-black uppercase tracking-normal text-[#073f8f]">{labels.registerEyebrow}</p>
          <h1 className="mt-5 max-w-md text-4xl font-black leading-tight text-[#061d49]">{labels.registerTitle}</h1>
          <p className="mt-6 max-w-sm text-base leading-8 text-slate-600">{labels.registerText}</p>
          <ul className="mt-10 space-y-5">
            {labels.registerBenefits.map((benefit) => (
              <li className="flex items-center gap-4 text-sm font-bold text-slate-600" key={benefit}>
                <span className="grid h-7 w-7 place-items-center rounded-md bg-blue-50 text-[#073f8f]">
                  <AuthIcon className="h-4 w-4" name="sparkle" />
                </span>
                {benefit}
              </li>
            ))}
          </ul>
          <div className="relative mt-12 h-96 max-w-sm overflow-hidden rounded-[44%_56%_50%_50%/42%_40%_60%_58%] bg-blue-50">
            <img alt={labels.studentImageAlt} className="absolute inset-x-0 bottom-0 mx-auto h-full w-full object-cover object-[50%_34%]" src={studentImage} />
          </div>
        </aside>

        <div className="mx-auto w-full max-w-xl">
          {/* Formulaire public: seuls les roles etudiant et conseiller sont proposes ici. */}
          <form className="rounded-lg border border-slate-100 bg-white p-7 shadow-2xl shadow-blue-950/10 sm:p-10" data-table-users="users" data-table-profile="profils_etudiants" onSubmit={handleSubmit}>
            <h2 className="text-3xl font-black text-[#061d49]">{labels.registerFormTitle}</h2>
            <p className="mt-3 text-sm text-slate-500">{labels.registerFormText}</p>

            <input name="name" readOnly type="hidden" value={fullName} />
            <input name="statut" readOnly type="hidden" value={role === 'conseiller' ? 'en_attente' : 'actif'} />
            <input name="langue_preferee" readOnly type="hidden" value={language} />

            {/* Le choix du role pilote aussi la validation serveur: specialite requise pour conseiller. */}
            <fieldset className="mt-8">
              <legend className="text-sm font-black text-[#06255a]">{labels.roleLegend}</legend>
              <div className="mt-2 grid gap-3 sm:grid-cols-2">
                {[
                  { label: labels.student, value: 'etudiant' },
                  { label: labels.counselor, value: 'conseiller' },
                ].map((option) => (
                  <label
                    className={`flex min-h-12 cursor-pointer items-center justify-center rounded-md border px-4 text-sm font-black ${
                      role === option.value
                        ? 'border-[#073f8f] bg-blue-50 text-[#073f8f]'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                    }`}
                    key={option.value}
                  >
                    <input checked={role === option.value} className="sr-only" name="role" onChange={() => setRole(option.value)} type="radio" value={option.value} />
                    {option.label}
                  </label>
                ))}
              </div>
            </fieldset>

            <div className="mt-8 grid gap-5 sm:grid-cols-2">
              <TextField autoComplete="given-name" icon="user" label={labels.firstName} name="prenom" onChange={(event) => setIdentity((current) => ({ ...current, prenom: event.target.value }))} placeholder={labels.firstNamePlaceholder} value={identity.prenom} />
              <TextField autoComplete="family-name" icon="user" label={labels.lastName} name="nom" onChange={(event) => setIdentity((current) => ({ ...current, nom: event.target.value }))} placeholder={labels.lastNamePlaceholder} value={identity.nom} />
            </div>

            <div className="mt-5 space-y-5">
              <TextField autoComplete="email" icon="mail" label={labels.email} name="email" placeholder={labels.emailPlaceholder} type="email" />
              <TextField autoComplete="tel" icon="phone" label={labels.phone} name="telephone" placeholder={labels.phonePlaceholder} type="tel" />
              {role === 'conseiller' && (
                <TextField autoComplete="organization-title" icon="user" label={labels.specialty} name="specialite" placeholder={labels.specialtyPlaceholder} />
              )}
              <TextField autoComplete="new-password" icon="lock" label={labels.password} name="password" placeholder={labels.newPasswordPlaceholder} showPasswordLabel={labels.showPassword} type="password" />
              <TextField autoComplete="new-password" icon="lock" label={labels.confirmPassword} name="password_confirmation" placeholder={labels.confirmPasswordPlaceholder} showPasswordLabel={labels.showPassword} type="password" />
            </div>

            {/* Acceptation legale obligatoire; les textes complets s'ouvrent dans la modale globale. */}
            <label className="mt-5 flex gap-3 text-sm leading-6 text-slate-600">
              <input className="mt-1 h-4 w-4 rounded border-slate-300 text-[#073f8f]" name="conditions_acceptees" type="checkbox" />
              <span>
                {labels.acceptStart}{' '}
                <button className="focus-ring rounded-sm font-black text-[#073f8f]" onClick={(event) => {
                    event.preventDefault()
                    onOpenLegal?.('conditions')
                  }} type="button">
                  {labels.terms}
                </button>{' '}
                {labels.andPolicy}{' '}
                <button className="focus-ring rounded-sm font-black text-[#073f8f]" onClick={(event) => {
                    event.preventDefault()
                    onOpenLegal?.('politique')
                  }} type="button">
                  {labels.privacy}
                </button>
              </span>
            </label>

            {status.message && (
              // Message API: succes de creation ou erreur de validation formulaire.
              <p className={`mt-5 rounded-md px-4 py-3 text-sm font-bold ${status.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
                {status.message}
              </p>
            )}

            <button className="focus-ring mt-7 min-h-12 w-full rounded-md bg-[#073f8f] px-5 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-[#052f6f] disabled:cursor-not-allowed disabled:opacity-70" disabled={isSubmitting} type="submit">
              {isSubmitting ? labels.registerLoading : labels.registerButton}
            </button>

            <div className="my-8 flex items-center gap-4 text-xs text-slate-400">
              <span className="h-px flex-1 bg-slate-200" />
              {labels.divider}
              <span className="h-px flex-1 bg-slate-200" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              {/* Boutons sociaux visuels en attendant le branchement OAuth reel. */}
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
              {labels.hasAccount}{' '}
              <a className="focus-ring rounded-sm font-black text-[#073f8f]" href="#connexion">
                {labels.loginLink}
              </a>
            </p>
          </form>

          <div className="mt-8 flex gap-5 rounded-lg bg-blue-50 p-6 text-[#073f8f]">
            <AuthIcon className="h-8 w-8 shrink-0" name="verify" />
            <div>
              <h3 className="font-black">{labels.verificationTitle}</h3>
              <p className="mt-2 text-sm leading-6 text-slate-600">{labels.verificationText}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
