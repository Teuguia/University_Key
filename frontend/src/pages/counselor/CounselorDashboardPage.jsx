// Commentaire d'intention: compose le tableau de bord dedie aux conseillers.

import { useEffect, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

const navIcons = ['user', 'mail', 'calendar', 'file', 'history', 'users', 'star', 'book', 'star', 'settings']

const fallbackData = {
  counselor: {
    name: 'Conseiller',
    first_name: 'Conseiller',
    specialty: "Conseiller d'orientation",
    photo_url: null,
    is_available: true,
  },
  metrics: {
    students_followed: 0,
    tests_reviewed: 0,
    unread_messages: 0,
    positive_reviews: 0,
    rating: { average: 0, count: 0 },
  },
  recent_tests: [],
  messages: [],
  appointments: [],
  created_tests: [],
}

// Icones locales pour eviter une dependance et garder le meme style que les autres dashboards.
function CounselorIcon({ name, className = 'h-5 w-5' }) {
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
    book: <svg {...common}><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z" /><path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20" /></svg>,
    calendar: <svg {...common}><rect height="18" rx="2" width="18" x="3" y="4" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>,
    clipboard: <svg {...common}><path d="M9 5h6" /><path d="M9 3h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1V5a2 2 0 0 1 2-2Z" /></svg>,
    eye: <svg {...common}><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>,
    file: <svg {...common}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" /></svg>,
    grid: <svg {...common}><rect height="7" width="7" x="3" y="3" /><rect height="7" width="7" x="14" y="3" /><rect height="7" width="7" x="3" y="14" /><rect height="7" width="7" x="14" y="14" /></svg>,
    history: <svg {...common}><path d="M3 12a9 9 0 1 0 3-6.7" /><path d="M3 3v6h6" /><path d="M12 7v5l3 2" /></svg>,
    mail: <svg {...common}><path d="M4 4h16v16H4z" /><path d="m4 7 8 6 8-6" /></svg>,
    message: <svg {...common}><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" /></svg>,
    paper: <svg {...common}><path d="m22 2-7 20-4-9-9-4 20-7Z" /><path d="M22 2 11 13" /></svg>,
    settings: <svg {...common}><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.8 1.8 0 0 0-1-1.6 1.8 1.8 0 0 0-2 .4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.8 1.8 0 0 0 1.6-1 1.8 1.8 0 0 0-.4-2l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.8 1.8 0 0 0 1 1.6 1.8 1.8 0 0 0 2-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.8 1.8 0 0 0-1.6 1Z" /></svg>,
    star: <svg {...common}><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" /></svg>,
    user: <svg {...common}><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>,
    users: <svg {...common}><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" /></svg>,
  }

  return icons[name] ?? icons.grid
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

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value))
}

function MetricCard({ icon, label, value, detail, tone, action }) {
  return (
    <article className="min-h-36 rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
      <span className={`grid h-12 w-12 place-items-center rounded-full ${tone}`}>
        <CounselorIcon className="h-6 w-6" name={icon} />
      </span>
      <p className="mt-5 text-sm font-black text-[#061d49]">{label}</p>
      <p className="mt-2 text-3xl font-black text-[#061d49]">{value}</p>
      {action ? <p className="mt-2 text-sm font-black text-[#074fb2]">{action} &gt;</p> : <p className="mt-2 text-sm font-bold text-emerald-600">{detail}</p>}
    </article>
  )
}

function Panel({ title, action, children }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
      <div className="flex items-center justify-between gap-4">
        <h2 className="text-lg font-black text-[#061d49]">{title}</h2>
        {action && <a className="text-sm font-black text-[#074fb2]" href="#conseiller">{action}</a>}
      </div>
      <div className="mt-5">{children}</div>
    </section>
  )
}

/**
 * Dashboard affiche apres validation du compte conseiller par l'administrateur.
 */
export function CounselorDashboardPage({ labels }) {
  // Le fallback evite les ruptures visuelles pendant le chargement de l'API.
  const [dashboard, setDashboard] = useState(fallbackData)
  const [status, setStatus] = useState({ state: 'loading', message: '' })

  useEffect(() => {
    let isMounted = true

    // Route protegee par Sanctum et par le statut actif du compte conseiller.
    apiRequest('/counselor/dashboard')
      .then((payload) => {
        if (isMounted) {
          setDashboard({ ...fallbackData, ...payload.data })
          setStatus({ state: 'ready', message: '' })
        }
      })
      .catch((error) => {
        if (isMounted) {
          setStatus({ state: 'error', message: error.message })
        }
      })

    return () => {
      isMounted = false
    }
  }, [])

  const counselor = dashboard.counselor
  const metrics = dashboard.metrics
  const rating = metrics.rating ?? { average: 0, count: 0 }

  return (
    <section className="min-h-screen bg-slate-50">
      <div className="mx-auto grid max-w-[96rem] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[19rem_1fr_24rem] lg:px-8">
        {/* Sidebar de travail du conseiller: profil, modules et action commerciale. */}
        <aside className="hidden lg:block">
          <div className="rounded-lg bg-[#06265c] p-5 text-white shadow-sm">
            <div className="flex items-center gap-3">
              {counselor.photo_url ? (
                <img alt="" className="h-14 w-14 rounded-full object-cover" src={counselor.photo_url} />
              ) : (
                <span className="grid h-14 w-14 place-items-center rounded-full bg-white text-sm font-black text-[#06265c]">{initials(counselor.name)}</span>
              )}
              <div>
                <p className="font-black">{counselor.name}</p>
                <p className="text-sm text-blue-100">{counselor.specialty}</p>
                <p className="mt-1 text-xs font-black text-emerald-300">{labels.online}</p>
              </div>
            </div>

            <nav className="mt-8 space-y-1">
              <a className="flex min-h-12 items-center gap-3 rounded-md bg-[#074fb2] px-4 text-sm font-black" href="#conseiller">
                <CounselorIcon name="grid" />
                {labels.dashboard}
              </a>
              {labels.nav.map((item, index) => (
                <a className="flex min-h-11 items-center gap-3 rounded-md px-4 text-sm font-bold text-blue-50 hover:bg-white/10" href={index === 1 ? '#communications' : '#conseiller'} key={item}>
                  <CounselorIcon className="h-5 w-5" name={navIcons[index]} />
                  {item}
                  {item === labels.nav[1] && metrics.unread_messages > 0 && (
                    <span className="ml-auto grid h-6 min-w-6 place-items-center rounded-full bg-emerald-500 px-2 text-xs text-white">{metrics.unread_messages}</span>
                  )}
                </a>
              ))}
            </nav>

            <div className="mt-8 rounded-lg bg-[#073f8f] p-4">
              <span className="grid h-10 w-10 place-items-center rounded-md bg-white/10">
                <CounselorIcon name="paper" />
              </span>
              <h2 className="mt-4 font-black">{labels.boostTitle}</h2>
              <p className="mt-2 text-sm leading-6 text-blue-100">{labels.boostText}</p>
              <button className="mt-5 min-h-10 w-full rounded-md border border-white/25 text-sm font-black text-white" type="button">
                {labels.boostButton}
              </button>
            </div>
          </div>
        </aside>

        <main className="min-w-0 space-y-5">
          {status.state === 'error' && (
            <p className="rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{status.message}</p>
          )}

          <section>
            <h1 className="text-3xl font-black text-[#061d49]">
              {labels.welcome}, {counselor.first_name}
            </h1>
            <p className="mt-2 text-sm font-bold text-slate-500">{labels.subtitle}</p>
          </section>

          <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard detail={`+ ${labels.thisMonth}`} icon="users" label={labels.studentsFollowed} tone="bg-emerald-50 text-emerald-700" value={metrics.students_followed} />
            <MetricCard detail={`+ ${labels.thisMonth}`} icon="clipboard" label={labels.testsReviewed} tone="bg-blue-50 text-blue-700" value={metrics.tests_reviewed} />
            <MetricCard action={labels.viewMessages} icon="message" label={labels.unreadMessages} tone="bg-violet-50 text-violet-700" value={metrics.unread_messages} />
            <MetricCard detail={`${labels.basedOn} ${rating.count} ${labels.reviews}`} icon="star" label={labels.positiveReviews} tone="bg-orange-50 text-orange-700" value={`${rating.average || 0}/5`} />
          </section>

          <Panel action={labels.viewAll} title={labels.recentTests}>
            {dashboard.recent_tests.length === 0 ? (
              <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{labels.noTests}</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-slate-100 text-xs font-black text-slate-500">
                    <tr>
                      <th className="py-3">{labels.student}</th>
                      <th className="py-3">{labels.test}</th>
                      <th className="py-3">{labels.date}</th>
                      <th className="py-3">{labels.globalResult}</th>
                      <th className="py-3">{labels.actions}</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {dashboard.recent_tests.map((test) => (
                      <tr key={test.id}>
                        <td className="py-3 font-bold text-[#061d49]">{test.student}</td>
                        <td className="py-3 text-slate-600">{test.test}</td>
                        <td className="py-3 text-slate-600">{formatDate(test.date)}</td>
                        <td className="py-3">
                          <span className={`rounded-md px-3 py-1 text-xs font-black ${test.score >= 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700'}`}>
                            {test.score >= 80 ? labels.high : labels.medium} ({test.score}%)
                          </span>
                        </td>
                        <td className="py-3">
                          <button className="rounded-md p-2 text-[#074fb2] hover:bg-blue-50" type="button">
                            <CounselorIcon className="h-4 w-4" name="eye" />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Panel>

          <Panel action={labels.viewAll} title={labels.createdTests}>
            {dashboard.created_tests.length === 0 ? (
              <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{labels.noCreatedTests}</p>
            ) : (
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {dashboard.created_tests.map((test) => (
                  <article className="rounded-lg border border-slate-200 p-4" key={test.id}>
                    <div className="flex items-start gap-3">
                      <span className="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-[#074fb2]">
                        <CounselorIcon name="clipboard" />
                      </span>
                      <div>
                        <h3 className="font-black text-[#061d49]">{test.title}</h3>
                        <p className="mt-1 text-sm font-bold text-slate-500">{test.questions} {labels.questions}</p>
                      </div>
                    </div>
                    <p className="mt-4 text-xs font-bold text-slate-500">{labels.used} {test.used} {labels.times}</p>
                  </article>
                ))}
              </div>
            )}
          </Panel>
        </main>

        <aside className="space-y-5">
          <Panel action={labels.viewAllPlural} title={labels.messages}>
            {dashboard.messages.length === 0 ? (
              <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{labels.noMessages}</p>
            ) : (
              <div className="space-y-4">
                {dashboard.messages.map((message) => (
                  <article className="flex gap-3 border-b border-slate-100 pb-3 last:border-b-0" key={message.id}>
                    <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 text-xs font-black text-[#074fb2]">{initials(message.sender)}</span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-black text-[#061d49]">{message.sender}</p>
                      <p className="truncate text-xs font-bold text-slate-500">{message.excerpt}</p>
                    </div>
                    {message.is_unread && <span className="mt-2 h-2 w-2 rounded-full bg-emerald-500" />}
                  </article>
                ))}
              </div>
            )}
          </Panel>

          <Panel action={labels.viewAll} title={labels.appointments}>
            {dashboard.appointments.length === 0 ? (
              <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{labels.noAppointments}</p>
            ) : (
              <div className="space-y-3">
                {dashboard.appointments.map((appointment) => (
                  <article className="flex items-center gap-4 rounded-lg border border-slate-200 p-3" key={appointment.id}>
                    <div className="grid h-14 w-14 place-items-center rounded-md bg-blue-50 text-center text-[#074fb2]">
                      <span className="block text-xl font-black leading-none">{appointment.day}</span>
                      <span className="block text-[10px] font-black uppercase">{appointment.month}</span>
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-black text-[#061d49]">{appointment.student}</p>
                      <p className="text-xs font-bold text-slate-500">{appointment.time}</p>
                    </div>
                    <span className={`rounded-md px-3 py-1 text-xs font-black ${appointment.status === 'confirme' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700'}`}>
                      {appointment.status === 'confirme' ? labels.confirmed : labels.pending}
                    </span>
                  </article>
                ))}
              </div>
            )}
          </Panel>
        </aside>
      </div>
    </section>
  )
}
