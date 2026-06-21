import { useEffect, useMemo, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

const heroImage = '/images/hero-student.png'

// Les libelles de navigation viennent du dictionnaire; cette liste conserve seulement les icones.
const navIcons = ['home', 'user', 'clipboard', 'chart', 'graduation', 'school', 'users', 'message', 'bell', 'settings']

function DashboardIcon({ name, className = 'h-5 w-5' }) {
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
    bell: (
      <svg {...common}>
        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
        <path d="M13.7 21a2 2 0 0 1-3.4 0" />
      </svg>
    ),
    book: (
      <svg {...common}>
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
        <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z" />
      </svg>
    ),
    briefcase: (
      <svg {...common}>
        <rect height="14" rx="2" width="20" x="2" y="7" />
        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M2 12h20" />
      </svg>
    ),
    calendar: (
      <svg {...common}>
        <rect height="18" rx="2" width="18" x="3" y="4" />
        <path d="M16 2v4M8 2v4M3 10h18" />
      </svg>
    ),
    chart: (
      <svg {...common}>
        <path d="M3 3v18h18" />
        <path d="M8 17V9M13 17V5M18 17v-6" />
      </svg>
    ),
    clipboard: (
      <svg {...common}>
        <path d="M9 5h6" />
        <path d="M9 3h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1V5a2 2 0 0 1 2-2Z" />
        <path d="m9 14 2 2 4-5" />
      </svg>
    ),
    code: (
      <svg {...common}>
        <path d="m16 18 6-6-6-6M8 6l-6 6 6 6" />
      </svg>
    ),
    graduation: (
      <svg {...common}>
        <path d="m22 10-10-5-10 5 10 5 10-5Z" />
        <path d="M6 12v5c3.5 2 8.5 2 12 0v-5" />
      </svg>
    ),
    grid: (
      <svg {...common}>
        <rect height="7" width="7" x="3" y="3" />
        <rect height="7" width="7" x="14" y="3" />
        <rect height="7" width="7" x="3" y="14" />
        <rect height="7" width="7" x="14" y="14" />
      </svg>
    ),
    heart: (
      <svg {...common}>
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" />
      </svg>
    ),
    home: (
      <svg {...common}>
        <path d="m3 11 9-8 9 8" />
        <path d="M5 10v11h14V10" />
      </svg>
    ),
    message: (
      <svg {...common}>
        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
      </svg>
    ),
    school: (
      <svg {...common}>
        <path d="M3 21h18M5 21V9l7-4 7 4v12M9 21v-6h6v6" />
      </svg>
    ),
    search: (
      <svg {...common}>
        <circle cx="11" cy="11" r="7" />
        <path d="m21 21-4.3-4.3" />
      </svg>
    ),
    settings: (
      <svg {...common}>
        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
        <path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.8 1.8 0 0 0-1-1.6 1.8 1.8 0 0 0-2 .4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.8 1.8 0 0 0 1.6-1 1.8 1.8 0 0 0-.4-2l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.8 1.8 0 0 0 1 1.6 1.8 1.8 0 0 0 2-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.8 1.8 0 0 0-1.6 1Z" />
      </svg>
    ),
    star: (
      <svg {...common}>
        <path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" />
      </svg>
    ),
    user: (
      <svg {...common}>
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4 21a8 8 0 0 1 16 0" />
      </svg>
    ),
    users: (
      <svg {...common}>
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />
      </svg>
    ),
  }

  return icons[name] ?? icons.home
}

function initials(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function formatDate(value, emptyLabel) {
  if (!value) {
    return emptyLabel
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value))
}

function EmptyState({ children }) {
  return <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{children}</p>
}

function MetricCard({ icon, label, value, tone, action }) {
  return (
    <article className="flex min-h-28 items-center gap-4 border-b border-slate-200 px-5 py-5 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0">
      <span className={`grid h-12 w-12 shrink-0 place-items-center rounded-full ${tone}`}>
        <DashboardIcon className="h-6 w-6" name={icon} />
      </span>
      <div className="min-w-0">
        <p className="text-3xl font-black text-[#061d49]">{value}</p>
        <p className="mt-1 text-xs font-bold text-slate-500">{label}</p>
        {action && <p className="mt-3 text-sm font-black text-[#074fb2]">{action}</p>}
      </div>
    </article>
  )
}

/**
 * Affiche le tableau de bord etudiant personnalise apres inscription.
 */
export function StudentDashboardPage({ labels }) {
  // dashboard contient la reponse agregee du backend; status pilote chargement/erreur.
  const [dashboard, setDashboard] = useState(null)
  const [status, setStatus] = useState({ state: 'loading', message: '' })
  const [photoStatus, setPhotoStatus] = useState({ state: 'idle', message: '' })
  const [searchQuery, setSearchQuery] = useState('')
  const [searchResults, setSearchResults] = useState([])
  const [searchStatus, setSearchStatus] = useState('idle')
  const [isSearchOpen, setIsSearchOpen] = useState(false)

  useEffect(() => {
    let isMounted = true

    async function loadDashboard() {
      try {
        // Endpoint protege qui renvoie profil, KPI, recommandations, messages et rappels.
        const payload = await apiRequest('/student/dashboard')

        if (isMounted) {
          setDashboard(payload.data)
          setStatus({ state: 'ready', message: '' })
        }
      } catch (error) {
        if (isMounted) {
          setStatus({ state: 'error', message: error.message })
        }
      }
    }

    loadDashboard()

    return () => {
      isMounted = false
    }
  }, [])

  useEffect(() => {
    const query = searchQuery.trim()
    let isCurrentSearch = true

    if (query.length < 2) {
      const resetTimeoutId = window.setTimeout(() => {
        if (isCurrentSearch) {
          setSearchResults([])
          setSearchStatus('idle')
        }
      }, 0)

      return () => {
        isCurrentSearch = false
        window.clearTimeout(resetTimeoutId)
      }
    }

    const timeoutId = window.setTimeout(() => {
      setSearchStatus('loading')

      apiRequest(`/recherche?q=${encodeURIComponent(query)}`)
        .then((payload) => {
          if (isCurrentSearch) {
            setSearchResults(payload.data ?? [])
            setSearchStatus('ready')
            setIsSearchOpen(true)
          }
        })
        .catch(() => {
          if (isCurrentSearch) {
            setSearchResults([])
            setSearchStatus('error')
            setIsSearchOpen(true)
          }
        })
    }, 300)

    return () => {
      isCurrentSearch = false
      window.clearTimeout(timeoutId)
    }
  }, [searchQuery])

  const student = dashboard?.student
  const metrics = dashboard?.metrics ?? {}
  const profileCompletion = dashboard?.profile_completion?.percentage ?? 0
  const compatibility = dashboard?.compatibility
  const domains = useMemo(() => {
    const apiDomains = dashboard?.domains ?? []
    // Si le catalogue backend est vide, on affiche des domaines localises par defaut.
    return apiDomains.length ? apiDomains : labels.domains
  }, [dashboard?.domains, labels.domains])

  async function handlePhotoChange(event) {
    const [photo] = event.target.files ?? []

    if (!photo) {
      return
    }

    const formData = new FormData()
    formData.append('photo', photo)
    setPhotoStatus({ state: 'loading', message: labels.uploadLoading })

    try {
      // Upload multipart: le client API laisse le navigateur definir le Content-Type.
      const payload = await apiRequest('/student/profile/photo', {
        method: 'POST',
        body: formData,
      })

      // Mise a jour locale pour afficher la nouvelle photo sans recharger toute la page.
      setDashboard((current) => ({
        ...current,
        student: payload.data.student,
        profile_completion: payload.data.profile_completion,
        compatibility: payload.data.compatibility,
        metrics: {
          ...current.metrics,
          max_compatibility: payload.data.compatibility.score,
        },
      }))
      setPhotoStatus({ state: 'success', message: payload.message })
    } catch (error) {
      setPhotoStatus({ state: 'error', message: error.message })
    } finally {
      event.target.value = ''
    }
  }

  function openSearchResult(result) {
    setIsSearchOpen(false)
    setSearchQuery(result.title)
  }

  if (status.state === 'loading') {
    return (
      <section className="min-h-screen bg-slate-50 px-4 py-10 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-7xl">
          <p className="sr-only">{labels.loadingTitle}</p>
          <div className="h-10 w-72 animate-pulse rounded-md bg-slate-200" />
          <div className="mt-8 grid gap-5 lg:grid-cols-[16rem_1fr_22rem]">
            <div className="h-[34rem] animate-pulse rounded-lg bg-white" />
            <div className="h-[34rem] animate-pulse rounded-lg bg-white" />
            <div className="h-[34rem] animate-pulse rounded-lg bg-white" />
          </div>
        </div>
      </section>
    )
  }

  if (status.state === 'error') {
    return (
      <section className="min-h-screen bg-slate-50 px-4 py-16 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-xl rounded-lg border border-red-100 bg-white p-8 text-center shadow-sm">
          <h1 className="text-2xl font-black text-[#061d49]">{labels.unavailableTitle}</h1>
          <p className="mt-3 text-sm font-bold text-red-600">{status.message}</p>
          <a className="mt-6 inline-flex min-h-11 items-center rounded-md bg-[#073f8f] px-5 text-sm font-black text-white" href="#connexion">
            {labels.login}
          </a>
        </div>
      </section>
    )
  }

  return (
    <section className="min-h-screen bg-slate-50">
      <div className="mx-auto grid max-w-[96rem] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[16rem_1fr_22rem] lg:px-8">
        {/* Navigation laterale desktop de l'espace etudiant. */}
        <aside className="hidden lg:block">
          <nav className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm shadow-slate-200/60">
            {labels.nav.map((label, index) => (
              <a
                className={`flex min-h-12 items-center gap-3 rounded-md px-4 text-sm font-black ${
                  index === 0 ? 'bg-blue-50 text-[#074fb2]' : 'text-[#061d49] hover:bg-slate-50'
                }`}
                href="#dashboard"
                key={label}
              >
                <DashboardIcon className="h-5 w-5 shrink-0" name={navIcons[index]} />
                {label}
                {index === 7 && metrics.unread_messages > 0 && (
                  <span className="ml-auto grid h-6 min-w-6 place-items-center rounded-full bg-[#074fb2] px-2 text-xs text-white">
                    {metrics.unread_messages}
                  </span>
                )}
              </a>
            ))}
          </nav>

          <div className="mt-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <span className="grid h-12 w-12 place-items-center rounded-md bg-blue-50 text-[#074fb2]">
              <DashboardIcon className="h-6 w-6" name="graduation" />
            </span>
            <h2 className="mt-4 text-base font-black text-[#061d49]">{labels.helpTitle}</h2>
            <p className="mt-2 text-sm leading-6 text-slate-500">{labels.helpText}</p>
            <a className="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-md bg-[#073f8f] px-4 text-sm font-black text-white" href="#conseillers">
              {labels.contactCounselor}
            </a>
          </div>
        </aside>

        <main className="min-w-0 space-y-5">
          <section className="relative rounded-lg border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60">
            <div className="flex min-h-12 items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 text-slate-400">
              <DashboardIcon className="h-5 w-5 shrink-0" name="search" />
              <input
                className="w-full border-0 bg-transparent text-sm font-bold text-slate-900 outline-none placeholder:text-slate-400"
                onBlur={() => window.setTimeout(() => setIsSearchOpen(false), 150)}
                onChange={(event) => {
                  setSearchQuery(event.target.value)
                  setIsSearchOpen(true)
                }}
                onFocus={() => setIsSearchOpen(true)}
                placeholder="Rechercher une ecole, une filiere, une ville..."
                value={searchQuery}
              />
            </div>

            {isSearchOpen && searchQuery.trim().length >= 2 && (
              <div className="absolute left-4 right-4 top-[4.75rem] z-40 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl shadow-slate-200">
                {searchStatus === 'loading' && (
                  <p className="px-4 py-3 text-sm font-bold text-slate-500">Recherche en cours...</p>
                )}

                {searchStatus === 'error' && (
                  <p className="px-4 py-3 text-sm font-bold text-red-600">Recherche indisponible pour le moment.</p>
                )}

                {searchStatus === 'ready' && searchResults.length === 0 && (
                  <p className="px-4 py-3 text-sm font-bold text-slate-500">Aucun etablissement valide trouve.</p>
                )}

                {searchResults.map((result) => (
                  <a
                    className="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-blue-50"
                    href={result.url}
                    key={result.id}
                    onClick={() => openSearchResult(result)}
                  >
                    <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-md bg-blue-50 text-[#074fb2]">
                      <DashboardIcon className="h-5 w-5" name="school" />
                    </span>
                    <span className="min-w-0">
                      <span className="block truncate text-sm font-black text-[#061d49]">{result.title}</span>
                      <span className="block truncate text-xs font-bold text-slate-500">{result.subtitle}</span>
                      {result.matched_filieres?.length > 0 && (
                        <span className="mt-1 block truncate text-xs font-black text-emerald-700">
                          {result.matched_filieres.join(', ')}
                        </span>
                      )}
                    </span>
                  </a>
                ))}
              </div>
            )}
          </section>

          <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div className="grid min-h-64 gap-6 px-6 py-7 lg:grid-cols-[1fr_26rem] lg:px-8">
              <div className="self-center">
                <p className="text-sm font-black text-[#074fb2]">{labels.profileComplete} {profileCompletion}%</p>
                <h1 className="mt-3 text-3xl font-black leading-tight text-[#061d49] sm:text-4xl">
                  {labels.hello} {student?.first_name ?? labels.fallbackStudent}
                </h1>
                <p className="mt-4 max-w-xl text-base leading-7 text-slate-600">
                  {labels.heroText}
                </p>
                {compatibility && !compatibility.has_orientation_score && (
                  <p className="mt-3 text-sm font-bold text-slate-500">
                    {labels.compatibilityHint}
                  </p>
                )}
                <a className="mt-7 inline-flex min-h-12 items-center gap-3 rounded-md bg-[#073f8f] px-6 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-[#052f6f]" href="#tests">
                  {labels.takeTest}
                  <span aria-hidden="true">-&gt;</span>
                </a>
              </div>
              <div className="relative hidden min-h-56 items-end justify-center lg:flex">
                <div className="absolute inset-x-6 bottom-0 h-36 rounded-full bg-blue-50" />
                <img
                  alt=""
                  className="relative z-10 h-64 w-full object-contain object-bottom"
                  src={heroImage}
                />
              </div>
            </div>
          </section>

          <section className="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/60 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard action={labels.history} icon="clipboard" label={labels.testsDone} tone="bg-blue-50 text-[#074fb2]" value={metrics.tests_completed ?? 0} />
            <MetricCard action={labels.results} icon="star" label={labels.compatibility} tone="bg-emerald-50 text-emerald-700" value={`${metrics.max_compatibility ?? 0}%`} />
            <MetricCard action={labels.favorites} icon="school" label={labels.favoriteSchools} tone="bg-violet-50 text-violet-700" value={metrics.favorite_schools ?? 0} />
            <MetricCard action={labels.openMessages} icon="users" label={labels.counselorsTalking} tone="bg-orange-50 text-orange-700" value={metrics.open_conversations ?? 0} />
          </section>

          <div className="grid gap-5 xl:grid-cols-2">
            <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
              <div className="flex items-center justify-between gap-4">
                <h2 className="text-lg font-black text-[#061d49]">{labels.recentTests}</h2>
                <a className="text-sm font-black text-[#074fb2]" href="#tests">{labels.viewAll}</a>
              </div>
              <div className="mt-5 space-y-3">
                {dashboard.recent_tests.length === 0 ? (
                  <EmptyState>{labels.noTests}</EmptyState>
                ) : (
                  dashboard.recent_tests.map((test) => (
                    <article className="flex min-h-16 items-center gap-4 rounded-md border border-slate-100 bg-white px-4 py-3 shadow-sm" key={test.id}>
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-blue-50 text-[#074fb2]">
                        <DashboardIcon name="clipboard" />
                      </span>
                      <div className="min-w-0 flex-1">
                        <h3 className="truncate text-sm font-black text-[#061d49]">{test.title}</h3>
                        <p className="mt-1 text-xs font-bold text-slate-500">{formatDate(test.completed_at, labels.noDate)}</p>
                      </div>
                      <p className="text-right text-sm font-black text-[#061d49]">{test.score ?? 0}%</p>
                    </article>
                  ))
                )}
              </div>
            </section>

            <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
              <div className="flex items-center justify-between gap-4">
                <h2 className="text-lg font-black text-[#061d49]">{labels.recommendedPrograms}</h2>
                <a className="text-sm font-black text-[#074fb2]" href="#filieres">{labels.viewAll}</a>
              </div>
              <div className="mt-5 space-y-4">
                {dashboard.recommended_programs.length === 0 ? (
                  <EmptyState>{labels.noPrograms}</EmptyState>
                ) : (
                  dashboard.recommended_programs.map((program) => (
                    <article className="grid gap-2" key={program.id}>
                      <div className="flex items-center justify-between gap-3 text-sm">
                        <span className="font-black text-[#061d49]">{program.name}</span>
                        <span className="font-black text-[#061d49]">{program.score}%</span>
                      </div>
                      <div className="h-2 rounded-full bg-slate-100">
                        <span className="block h-2 rounded-full bg-[#074fb2]" style={{ width: `${Math.min(program.score, 100)}%` }} />
                      </div>
                    </article>
                  ))
                )}
              </div>
            </section>
          </div>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-lg font-black text-[#061d49]">{labels.domainsTitle}</h2>
              <a className="text-sm font-black text-[#074fb2]" href="#filieres">{labels.seeMore}</a>
            </div>
            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
              {domains.map((domain, index) => (
                <a className="flex min-h-20 flex-col items-center justify-center rounded-md border border-slate-200 bg-white text-center text-sm font-black text-[#061d49] hover:border-[#074fb2] hover:bg-blue-50" href="#filieres" key={`${domain.slug}-${index}`}>
                  <DashboardIcon className="mb-2 h-6 w-6 text-[#074fb2]" name={['heart', 'code', 'briefcase', 'school', 'settings', 'grid'][index] ?? 'grid'} />
                  {domain.name}
                </a>
              ))}
            </div>
          </section>
        </main>

        <aside className="space-y-5">
          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div className="flex items-center gap-3">
              {student?.photo_url ? (
                <img alt="" className="h-14 w-14 rounded-full object-cover" src={student.photo_url} />
              ) : (
                <span className="grid h-14 w-14 place-items-center rounded-full bg-[#061d49] text-sm font-black text-white">
                  {initials(student?.name)}
                </span>
              )}
              <div>
                <h2 className="text-base font-black text-[#061d49]">{student?.name}</h2>
                <p className="text-sm font-bold text-slate-500">{student?.city || student?.region || labels.profileStudent}</p>
              </div>
            </div>
            <label className="mt-4 inline-flex min-h-10 w-full cursor-pointer items-center justify-center rounded-md border border-slate-200 bg-white px-4 text-sm font-black text-[#074fb2] hover:bg-blue-50">
              {photoStatus.state === 'loading' ? labels.uploadLoading : labels.uploadButton}
              <input accept="image/*" className="sr-only" onChange={handlePhotoChange} type="file" />
            </label>
            {photoStatus.message && (
              <p className={`mt-3 text-xs font-bold ${photoStatus.state === 'error' ? 'text-red-600' : 'text-emerald-700'}`}>
                {photoStatus.message}
              </p>
            )}
          </section>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-lg font-black text-[#061d49]">{labels.recentMessages}</h2>
              <a className="text-sm font-black text-[#074fb2]" href="#messages">{labels.viewAll}</a>
            </div>
            <div className="mt-5 space-y-4">
              {dashboard.recent_messages.length === 0 ? (
                <EmptyState>{labels.noMessages}</EmptyState>
              ) : (
                dashboard.recent_messages.map((message) => (
                  <article className="flex gap-3" key={message.id}>
                    <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 text-xs font-black text-[#074fb2]">
                      {initials(message.sender)}
                    </span>
                    <div className="min-w-0 flex-1">
                      <h3 className="truncate text-sm font-black text-[#061d49]">{message.sender}</h3>
                      <p className="truncate text-xs font-bold text-slate-500">{message.excerpt}</p>
                    </div>
                    {message.is_unread && <span className="mt-2 h-2 w-2 rounded-full bg-[#074fb2]" />}
                  </article>
                ))
              )}
            </div>
          </section>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-lg font-black text-[#061d49]">{labels.reminders}</h2>
              <a className="text-sm font-black text-[#074fb2]" href="#alertes">{labels.viewAll}</a>
            </div>
            <div className="mt-5 space-y-3">
              {dashboard.reminders.length === 0 ? (
                <EmptyState>{labels.noReminders}</EmptyState>
              ) : (
                dashboard.reminders.map((reminder) => (
                  <article className="flex min-h-16 items-center gap-3 rounded-md border border-slate-100 px-4 py-3" key={reminder.id}>
                    <DashboardIcon className="h-5 w-5 shrink-0 text-[#074fb2]" name={reminder.type === 'bourse' ? 'briefcase' : 'calendar'} />
                    <div className="min-w-0">
                      <h3 className="truncate text-sm font-black text-[#061d49]">{reminder.title}</h3>
                      <p className="truncate text-xs font-bold text-slate-500">{reminder.content}</p>
                    </div>
                  </article>
                ))
              )}
            </div>
          </section>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-lg font-black text-[#061d49]">{labels.recommendedSchools}</h2>
              <a className="text-sm font-black text-[#074fb2]" href="#ecoles">{labels.viewAll}</a>
            </div>
            <div className="mt-5 space-y-4">
              {dashboard.recommended_schools.length === 0 ? (
                <EmptyState>{labels.noSchools}</EmptyState>
              ) : (
                dashboard.recommended_schools.map((school) => (
                  <article className="flex items-center gap-3 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0" key={school.id}>
                    {school.logo_url ? (
                      <img alt="" className="h-10 w-10 rounded-full object-cover" src={school.logo_url} />
                    ) : (
                      <span className="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-[#074fb2]">
                        <DashboardIcon className="h-5 w-5" name="school" />
                      </span>
                    )}
                    <div className="min-w-0 flex-1">
                      <h3 className="truncate text-sm font-black text-[#061d49]">{school.name}</h3>
                      <p className="text-xs font-bold text-slate-500">{school.score}% {labels.match}</p>
                    </div>
                    <DashboardIcon className="h-5 w-5 text-slate-400" name="star" />
                  </article>
                ))
              )}
            </div>
          </section>
        </aside>
      </div>
    </section>
  )
}
