// Commentaire d'intention: compose le tableau de bord administrateur et ses outils de gestion.

import { useEffect, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

// Navigation laterale admin: chaque entree associe une icone locale a son libelle.
const sidebarGroups = [
  {
    title: 'Gestion',
    items: [
      { id: 'users', icon: 'users', label: 'Utilisateurs', target: 'admin-overview' },
      { id: 'students', icon: 'graduation', label: 'Etudiants', target: 'admin-detail-view', view: 'students' },
      { id: 'counselors', icon: 'users', label: 'Conseillers', target: 'admin-detail-view', view: 'counselors' },
      { id: 'schools', icon: 'school', label: 'Etablissements', target: 'admin-detail-view', view: 'schools' },
      { id: 'filieres', icon: 'briefcase', label: 'Filieres & metiers', target: 'admin-tests' },
      { id: 'tests', icon: 'clipboard', label: "Tests d'orientation", target: 'admin-tests', panel: 'orientation-tests' },
      { id: 'results', icon: 'chart', label: 'Resultats & recommandations', target: 'admin-detail-view', view: 'tests' },
      { id: 'messages', icon: 'mail', label: 'Messagerie', target: 'admin-overview', badge: 'messages' },
      { id: 'reports', icon: 'flag', label: 'Signalements', target: 'admin-alerts', badge: 'open_reports' },
      { id: 'validation', icon: 'shield', label: 'Validation des comptes', target: 'admin-detail-view', badge: 'pending_accounts', view: 'pending' },
      { id: 'privacy', icon: 'lock', label: 'Confidentialite & consentements', target: 'admin-legal-rules', panel: 'legal-rules' },
    ],
  },
  {
    title: 'Systeme',
    items: [
      { id: 'logs', icon: 'file', label: "Logs d'activite", target: 'admin-detail-view', view: 'logs' },
      { id: 'database', icon: 'database', label: 'Base de donnees', target: 'admin-alerts' },
      { id: 'security', icon: 'shield', label: 'Securite', target: 'admin-alerts' },
      { id: 'settings', icon: 'settings', label: 'Parametres', target: 'admin-overview' },
    ],
  },
]

// Donnees minimales affichees pendant le chargement ou si l'API retourne peu de contenu.
const fallbackData = {
  admin: { name: 'MINESEC', role_label: 'Super administrateur' },
  metrics: {
    students: 0,
    active_counselors: 0,
    pending_accounts: 0,
    tests_completed: 0,
    schools: 0,
    open_reports: 0,
  },
  pending_accounts: [],
  popular_tests: [],
  recent_activity: [],
  user_distribution: [],
  system_alerts: [],
}

const emptyTestForm = {
  titre: '',
  description: '',
  langue: 'fr',
  version: 1,
  duree_minutes: 20,
  statut: 'brouillon',
}

const weightFields = [
  ['sci', 'Sci'],
  ['tech', 'Tech'],
  ['com', 'Com'],
  ['sante', 'Sante'],
  ['lit', 'Lit'],
]

const emptyWeights = { sci: 0, tech: 0, com: 0, sante: 0, lit: 0 }

const axisFields = [
  ['log', 'Log'],
  ['soc', 'Soc'],
  ['crea', 'Crea'],
  ['lead', 'Lead'],
]

const emptyAxes = { log: 0, soc: 0, crea: 0, lead: 0 }

const detailViews = {
  students: { title: 'Etudiants inscrits', endpoint: '/admin/students' },
  counselors: { title: 'Conseillers actifs', endpoint: '/admin/counselors' },
  pending: { title: 'Comptes en attente', endpoint: '/admin/pending-accounts' },
  tests: { title: 'Tests effectues par les etudiants', endpoint: '/admin/test-sessions' },
  schools: { title: 'Ecoles enregistrees', endpoint: '/admin/schools' },
  logs: { title: "Journal d'activite administrateur", endpoint: '/admin/activity-logs' },
}

const adminEnglish = {
  'Gestion': 'Management',
  'Systeme': 'System',
  'Utilisateurs': 'Users',
  'Etudiants': 'Students',
  'Conseillers': 'Counselors',
  'Etablissements': 'Institutions',
  'Filieres & metiers': 'Programs & careers',
  "Tests d'orientation": 'Orientation tests',
  'Resultats & recommandations': 'Results & recommendations',
  'Messagerie': 'Messaging',
  'Signalements': 'Reports',
  'Validation des comptes': 'Account validation',
  'Confidentialite & consentements': 'Privacy & consent',
  "Logs d'activite": 'Activity logs',
  'Base de donnees': 'Database',
  'Securite': 'Security',
  'Parametres': 'Settings',
  'Tableau de bord': 'Dashboard',
  'Bonjour Administrateur': 'Hello Administrator',
  'Vue globale de la plateforme University Key': 'University Key platform overview',
  'Rechercher une ecole, une ville, une filiere...': 'Search for an institution, city, or program...',
  'Etudiants inscrits': 'Registered students',
  'Conseillers actifs': 'Active counselors',
  'Comptes en attente': 'Pending accounts',
  'Tests effectues': 'Completed tests',
  'Ecoles enregistrees': 'Registered institutions',
  'Signalements ouverts': 'Open reports',
  'Cliquer pour voir la liste': 'Click to view the list',
  'Cliquer pour valider': 'Click to validate',
  'Cliquer pour voir les passages': 'Click to view sessions',
  'Cliquer pour modifier': 'Click to edit',
  'a surveiller': 'to monitor',
  'Confidentialite et consentements': 'Privacy and consent',
  'Consentement et conditions d utilisation': 'Consent and terms of use',
  'Politique de confidentialite': 'Privacy policy',
  'Publier les modifications': 'Publish changes',
  'Publication en cours...': 'Publishing...',
  'Derniere publication :': 'Last publication:',
  'non disponible': 'unavailable',
  'Texte accepte par l utilisateur lors de la creation de son compte.': 'Text accepted by users when creating an account.',
  'Texte affiche par le bouton Confidentialite et dans le pied de page.': 'Text shown by the Privacy button and in the footer.',
  'Ces textes sont publies dans les boutons de consentement a l inscription et dans le pied de page. La syntaxe Markdown simple (#, ## et *) est prise en charge.': 'These texts are published in registration consent buttons and the footer. Simple Markdown (#, ## and *) is supported.',
}

function adminText(value, language) {
  return language === 'en' ? (adminEnglish[value] ?? value) : value
}

function createChoice(order = 1) {
  return {
    libelle: '',
    ordre: order,
    valeur: 0,
    metadata: { axes: { ...emptyAxes } },
    weights: { ...emptyWeights },
  }
}

function createQuestion(order = 1) {
  return {
    libelle: '',
    type: 'choix_unique',
    domaine: 'general',
    ordre: order,
    obligatoire: true,
    active: true,
    choices: [createChoice(1), createChoice(2)],
  }
}

// Icones SVG locales pour garder le dashboard admin autonome et coherent visuellement.
function AdminIcon({ name, className = 'h-5 w-5' }) {
  // Proprietes communes appliquees a toutes les icones du dashboard.
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

  // Dictionnaire d'icones utilise par la sidebar, les KPI et les panneaux.
  const icons = {
    bell: <svg {...common}><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" /><path d="M13.7 21a2 2 0 0 1-3.4 0" /></svg>,
    briefcase: <svg {...common}><rect height="14" rx="2" width="20" x="2" y="7" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M2 12h20" /></svg>,
    calendar: <svg {...common}><rect height="18" rx="2" width="18" x="3" y="4" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>,
    chart: <svg {...common}><path d="M3 3v18h18" /><path d="M8 17V9M13 17V5M18 17v-6" /></svg>,
    clipboard: <svg {...common}><path d="M9 5h6" /><path d="M9 3h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1V5a2 2 0 0 1 2-2Z" /></svg>,
    database: <svg {...common}><ellipse cx="12" cy="5" rx="8" ry="3" /><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5" /><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" /></svg>,
    download: <svg {...common}><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>,
    file: <svg {...common}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" /></svg>,
    flag: <svg {...common}><path d="M5 22V4" /><path d="M5 4h14l-2 5 2 5H5" /></svg>,
    graduation: <svg {...common}><path d="m22 10-10-5-10 5 10 5 10-5Z" /><path d="M6 12v5c3.5 2 8.5 2 12 0v-5" /></svg>,
    home: <svg {...common}><path d="m3 11 9-8 9 8" /><path d="M5 10v11h14V10" /></svg>,
    lock: <svg {...common}><rect height="11" rx="2" width="16" x="4" y="10" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>,
    mail: <svg {...common}><path d="M4 4h16v16H4z" /><path d="m4 7 8 6 8-6" /></svg>,
    search: <svg {...common}><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" /></svg>,
    settings: <svg {...common}><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.8 1.8 0 0 0-1-1.6 1.8 1.8 0 0 0-2 .4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.8 1.8 0 0 0 1.6-1 1.8 1.8 0 0 0-.4-2l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.8 1.8 0 0 0 1 1.6 1.8 1.8 0 0 0 2-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.8 1.8 0 0 0-1.6 1Z" /></svg>,
    shield: <svg {...common}><path d="M12 3 20 7v5c0 5-3 8-8 10-5-2-8-5-8-10V7l8-4Z" /></svg>,
    school: <svg {...common}><path d="M3 21h18M5 21V9l7-4 7 4v12M9 21v-6h6v6" /></svg>,
    users: <svg {...common}><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" /></svg>,
  }

  return icons[name] ?? icons.home
}

// Formate les compteurs du dashboard selon l'affichage francophone.
function formatNumber(value) {
  return new Intl.NumberFormat('fr-FR').format(value ?? 0)
}

function formatDate(value) {
  return value
    ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
    : '-'
}

// Cree les initiales du compte admin pour les avatars texte.
function initials(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

// Carte KPI reutilisee pour les six indicateurs principaux.
function MetricCard({ icon, label, value, tone, trend }) {
  return (
    <article className="min-h-32 rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
      <div className="flex items-center gap-4">
        <span className={`grid h-12 w-12 place-items-center rounded-full ${tone}`}>
          <AdminIcon className="h-6 w-6" name={icon} />
        </span>
        <div>
          <p className="text-xs font-black text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-black text-[#061d49]">{formatNumber(value)}</p>
        </div>
      </div>
      <p className="mt-4 text-xs font-black text-emerald-600">{trend}</p>
    </article>
  )
}

function ClickableMetricCard({ icon, label, value, tone, trend, active, onClick }) {
  return (
    <button
      className={`min-h-32 rounded-lg border bg-white p-5 text-left shadow-sm shadow-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-md ${active ? 'border-[#074fb2] ring-4 ring-blue-100' : 'border-slate-200'}`}
      onClick={onClick}
      type="button"
    >
      <div className="flex items-center gap-4">
        <span className={`grid h-12 w-12 place-items-center rounded-full ${tone}`}>
          <AdminIcon className="h-6 w-6" name={icon} />
        </span>
        <div>
          <p className="text-xs font-black text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-black text-[#061d49]">{formatNumber(value)}</p>
        </div>
      </div>
      <p className="mt-4 text-xs font-black text-emerald-600">{trend}</p>
    </button>
  )
}

function DetailTable({ columns, rows, emptyText }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead className="border-b border-slate-100 text-xs font-black uppercase text-slate-500">
          <tr>
            {columns.map((column) => (
              <th className="min-w-32 py-3 pr-4" key={column.key}>{column.label}</th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {rows.map((row) => (
            <tr key={row.id}>
              {columns.map((column) => (
                <td className="py-3 pr-4 font-bold text-slate-600" key={column.key}>
                  {column.render ? column.render(row) : (row[column.key] || 'Non precise')}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
      {rows.length === 0 && (
        <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">{emptyText}</p>
      )}
    </div>
  )
}

// Panneau generique avec titre, action optionnelle et contenu libre.
function Panel({ title, action, children }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
      <div className="flex items-center justify-between gap-4">
        <h2 className="text-lg font-black text-[#061d49]">{title}</h2>
        {action && <a className="text-sm font-black text-[#074fb2]" href="#admin">{action}</a>}
      </div>
      <div className="mt-5">{children}</div>
    </section>
  )
}

/**
 * Affiche le tableau de bord administrateur global.
 */
export function AdminDashboardPage({ language = 'fr' }) {
  // Le fallback evite un ecran vide pendant que les donnees admin arrivent de l'API.
  const [dashboard, setDashboard] = useState(fallbackData)
  const [status, setStatus] = useState({ state: 'loading', message: '' })
  const [tests, setTests] = useState([])
  const [testForm, setTestForm] = useState(emptyTestForm)
  const [testQuestions, setTestQuestions] = useState([])
  const [editingTestId, setEditingTestId] = useState(null)
  const [testStatus, setTestStatus] = useState({ state: '', message: '' })
  const [searchQuery, setSearchQuery] = useState('')
  const [searchResults, setSearchResults] = useState([])
  const [searchStatus, setSearchStatus] = useState('idle')
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [activeSidebarItem, setActiveSidebarItem] = useState('dashboard')
  const [activeDetailView, setActiveDetailView] = useState(null)
  const [isTestManagerOpen, setIsTestManagerOpen] = useState(false)
  const [detailRows, setDetailRows] = useState([])
  const [detailStatus, setDetailStatus] = useState({ state: 'idle', message: '' })
  const [schoolEdits, setSchoolEdits] = useState({})
  const [legalRules, setLegalRules] = useState({ conditions: '', politique: '', updated_at: null })
  const [legalStatus, setLegalStatus] = useState({ state: 'idle', message: '' })
  const [isLegalManagerOpen, setIsLegalManagerOpen] = useState(false)
  const t = (value) => adminText(value, language)

  useEffect(() => {
    let isMounted = true

    // Charge les statistiques admin protegees par Sanctum.
    apiRequest('/admin/dashboard')
      .then((payload) => {
        if (isMounted) {
          // Fusion defensive: si une section manque cote API, le fallback garde quan-même cette page stable.
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

    // Debounce 300 ms: on attend que l'utilisateur arrete de taper avant l'appel API.
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

  const metrics = dashboard.metrics

  function sidebarBadge(item) {
    if (item.badge === 'pending_accounts') {
      return metrics.pending_accounts
    }

    if (item.badge === 'open_reports') {
      return metrics.open_reports
    }

    if (item.badge === 'messages') {
      return 8
    }

    return null
  }

  function openSidebarItem(item) {
    setActiveSidebarItem(item.id)

    if (item.panel === 'orientation-tests') {
      openTestManager()
      return
    }

    if (item.panel === 'legal-rules') {
      openLegalRules()
      return
    }

    if (item.view) {
      loadDetailView(item.view)
      return
    }

    setActiveDetailView(null)
    setIsTestManagerOpen(false)
    setIsLegalManagerOpen(false)
    window.setTimeout(() => document.getElementById(item.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0)
  }

  async function reloadTests() {
    const payload = await apiRequest('/admin/orientation-tests')
    setTests(payload.data ?? [])
  }

  async function openTestManager() {
    setActiveDetailView(null)
    setIsTestManagerOpen(true)
    setIsLegalManagerOpen(false)
    setTestStatus({ state: 'loading', message: '' })

    try {
      await reloadTests()
      setTestStatus({ state: 'ready', message: '' })
      window.setTimeout(() => document.getElementById('admin-tests')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0)
    } catch (error) {
      setTestStatus({ state: 'error', message: error.message })
    }
  }

  async function openLegalRules() {
    setActiveDetailView(null)
    setIsTestManagerOpen(false)
    setIsLegalManagerOpen(true)
    setLegalStatus({ state: 'loading', message: '' })

    try {
      const payload = await apiRequest('/admin/regles')
      setLegalRules(payload.data ?? { conditions: '', politique: '', updated_at: null })
      setLegalStatus({ state: 'ready', message: '' })
      window.setTimeout(() => document.getElementById('admin-legal-rules')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0)
    } catch (error) {
      setLegalStatus({ state: 'error', message: error.message })
    }
  }

  async function saveLegalRules(event) {
    event.preventDefault()
    setLegalStatus({ state: 'loading', message: '' })

    try {
      const payload = await apiRequest('/admin/regles', {
        method: 'PATCH',
        body: JSON.stringify({ conditions: legalRules.conditions, politique: legalRules.politique }),
      })
      setLegalRules(payload.data)
      setLegalStatus({ state: 'success', message: payload.message })
    } catch (error) {
      setLegalStatus({ state: 'error', message: error.message })
    }
  }

  async function loadDetailView(view) {
    const config = detailViews[view]

    if (!config) {
      return
    }

    setActiveDetailView(view)
    setIsTestManagerOpen(false)
    setDetailStatus({ state: 'loading', message: '' })
    setDetailRows([])

    try {
      const payload = await apiRequest(config.endpoint)
      const rows = payload.data ?? []

      setDetailRows(rows)
      setSchoolEdits(view === 'schools' ? Object.fromEntries(rows.map((row) => [row.id, { ...row }])) : {})
      if (view === 'schools') {
        setDashboard((current) => ({
          ...current,
          metrics: {
            ...current.metrics,
            schools: rows.length,
          },
        }))
      }
      setDetailStatus({ state: 'ready', message: '' })
      window.setTimeout(() => document.getElementById('admin-detail-view')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0)
    } catch (error) {
      setDetailStatus({ state: 'error', message: error.message })
    }
  }

  function updateSchoolEdit(id, field, value) {
    setSchoolEdits((current) => ({
      ...current,
      [id]: { ...(current[id] ?? {}), [field]: value },
    }))
  }

  async function saveSchool(id) {
    const draft = schoolEdits[id]

    if (!draft) {
      return
    }

    setDetailStatus({ state: 'loading', message: '' })

    try {
      await apiRequest(`/admin/schools/${id}`, {
        method: 'PATCH',
        body: JSON.stringify(draft),
      })
      await loadDetailView('schools')
      setDetailStatus({ state: 'success', message: 'Ecole mise a jour.' })
    } catch (error) {
      setDetailStatus({ state: 'error', message: error.message })
    }
  }

  async function changeAccountStatus(userId, statut) {
    setDetailStatus({ state: 'loading', message: '' })

    try {
      await apiRequest(`/admin/users/${userId}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ statut }),
      })
      await loadDetailView(activeDetailView)
      setDetailStatus({ state: 'success', message: 'Statut du compte mis a jour.' })
    } catch (error) {
      setDetailStatus({ state: 'error', message: error.message })
    }
  }

  function resetTestForm() {
    setEditingTestId(null)
    setTestForm(emptyTestForm)
    setTestQuestions([])
    setTestStatus({ state: '', message: '' })
  }

  async function startEditTest(test) {
    setTestStatus({ state: 'loading', message: '' })

    try {
      const payload = await apiRequest(`/admin/orientation-tests/${test.id}`)
      const detailedTest = payload.data

      setEditingTestId(detailedTest.id)
      setTestForm({
        titre: detailedTest.title,
        description: detailedTest.description ?? '',
        langue: detailedTest.language ?? 'fr',
        version: detailedTest.version ?? 1,
        duree_minutes: detailedTest.duration_minutes ?? 20,
        statut: detailedTest.status ?? 'brouillon',
      })
      setTestQuestions((detailedTest.questions ?? []).map(normalizeQuestion))
      setTestStatus({ state: '', message: '' })
    } catch (error) {
      setTestStatus({ state: 'error', message: error.message })
    }
  }

  async function handleTestSubmit(event) {
    event.preventDefault()
    setTestStatus({ state: 'loading', message: '' })

    try {
      await apiRequest(editingTestId ? `/admin/orientation-tests/${editingTestId}` : '/admin/orientation-tests', {
        method: editingTestId ? 'PATCH' : 'POST',
        body: JSON.stringify({
          ...testForm,
          version: Number(testForm.version),
          duree_minutes: Number(testForm.duree_minutes),
          questions: testQuestions.map((question, questionIndex) => ({
            ...question,
            ordre: Number(question.ordre) || questionIndex + 1,
            choices: question.choices.map((choice, choiceIndex) => ({
              ...choice,
              ordre: Number(choice.ordre) || choiceIndex + 1,
              valeur: Number(choice.valeur) || 0,
              metadata: {
                ...(choice.metadata ?? {}),
                axes: Object.fromEntries(axisFields.map(([slug]) => [slug, Number(choice.metadata?.axes?.[slug]) || 0])),
              },
              weights: Object.fromEntries(weightFields.map(([slug]) => [slug, Number(choice.weights?.[slug]) || 0])),
            })),
          })),
        }),
      })
      await reloadTests()
      setTestStatus({ state: 'success', message: editingTestId ? 'Test mis a jour.' : 'Test cree.' })
      setEditingTestId(null)
      setTestForm(emptyTestForm)
      setTestQuestions([])
    } catch (error) {
      setTestStatus({ state: 'error', message: error.message })
    }
  }

  async function deleteTest(test) {
    const confirmed = window.confirm(`Supprimer le test "${test.title}" ? Cette action supprime aussi ses questions et sessions liees.`)

    if (!confirmed) {
      return
    }

    setTestStatus({ state: 'loading', message: '' })

    try {
      await apiRequest(`/admin/orientation-tests/${test.id}`, { method: 'DELETE' })
      await reloadTests()
      if (editingTestId === test.id) {
        resetTestForm()
      }
      setTestStatus({ state: 'success', message: 'Test supprime.' })
    } catch (error) {
      setTestStatus({ state: 'error', message: error.message })
    }
  }

  function normalizeQuestion(question, index) {
    return {
      libelle: question.libelle ?? '',
      type: question.type ?? 'choix_unique',
      domaine: question.domaine ?? 'general',
      ordre: question.ordre ?? index + 1,
      obligatoire: question.obligatoire ?? true,
      active: question.active ?? true,
      choices: (question.choices?.length ? question.choices : [createChoice(1), createChoice(2)]).map(normalizeChoice),
    }
  }

  function normalizeChoice(choice, index) {
    return {
      libelle: choice.libelle ?? '',
      ordre: choice.ordre ?? index + 1,
      valeur: choice.valeur ?? 0,
      metadata: {
        ...(choice.metadata ?? {}),
        axes: { ...emptyAxes, ...(choice.metadata?.axes ?? {}) },
      },
      weights: { ...emptyWeights, ...(choice.weights ?? {}) },
    }
  }

  function updateQuestion(index, field, value) {
    setTestQuestions((current) => current.map((question, questionIndex) => questionIndex === index ? { ...question, [field]: value } : question))
  }

  function addQuestion() {
    setTestQuestions((current) => [...current, createQuestion(current.length + 1)])
  }

  function removeQuestion(index) {
    setTestQuestions((current) => current.filter((_, questionIndex) => questionIndex !== index).map((question, questionIndex) => ({ ...question, ordre: questionIndex + 1 })))
  }

  function updateChoice(questionIndex, choiceIndex, field, value) {
    setTestQuestions((current) => current.map((question, currentQuestionIndex) => {
      if (currentQuestionIndex !== questionIndex) {
        return question
      }

      return {
        ...question,
        choices: question.choices.map((choice, currentChoiceIndex) => currentChoiceIndex === choiceIndex ? { ...choice, [field]: value } : choice),
      }
    }))
  }

  function updateChoiceWeight(questionIndex, choiceIndex, slug, value) {
    setTestQuestions((current) => current.map((question, currentQuestionIndex) => {
      if (currentQuestionIndex !== questionIndex) {
        return question
      }

      return {
        ...question,
        choices: question.choices.map((choice, currentChoiceIndex) => currentChoiceIndex === choiceIndex
          ? { ...choice, weights: { ...choice.weights, [slug]: value } }
          : choice),
      }
    }))
  }

  function updateChoiceAxis(questionIndex, choiceIndex, slug, value) {
    setTestQuestions((current) => current.map((question, currentQuestionIndex) => {
      if (currentQuestionIndex !== questionIndex) {
        return question
      }

      return {
        ...question,
        choices: question.choices.map((choice, currentChoiceIndex) => currentChoiceIndex === choiceIndex
          ? {
              ...choice,
              metadata: {
                ...(choice.metadata ?? {}),
                axes: { ...emptyAxes, ...(choice.metadata?.axes ?? {}), [slug]: value },
              },
            }
          : choice),
      }
    }))
  }

  function addChoice(questionIndex) {
    setTestQuestions((current) => current.map((question, currentQuestionIndex) => {
      if (currentQuestionIndex !== questionIndex) {
        return question
      }

      return {
        ...question,
        choices: [...question.choices, createChoice(question.choices.length + 1)],
      }
    }))
  }

  function removeChoice(questionIndex, choiceIndex) {
    setTestQuestions((current) => current.map((question, currentQuestionIndex) => {
      if (currentQuestionIndex !== questionIndex) {
        return question
      }

      return {
        ...question,
        choices: question.choices.filter((_, currentChoiceIndex) => currentChoiceIndex !== choiceIndex).map((choice, index) => ({ ...choice, ordre: index + 1 })),
      }
    }))
  }

  function openSearchResult(result) {
    setIsSearchOpen(false)
    setSearchQuery(result.title)
  }

  function renderDetailContent() {
    if (!activeDetailView) {
      return null
    }

    if (detailStatus.state === 'loading') {
      return <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Chargement...</p>
    }

    if (activeDetailView === 'students') {
      return (
        <DetailTable
          columns={[
            { key: 'name', label: 'Nom' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Telephone' },
            { key: 'city', label: 'Ville' },
            { key: 'bac', label: 'Bac' },
            { key: 'status', label: 'Statut' },
            { key: 'created_at', label: 'Inscription' },
          ]}
          emptyText="Aucun etudiant inscrit."
          rows={detailRows}
        />
      )
    }

    if (activeDetailView === 'counselors') {
      return (
        <DetailTable
          columns={[
            { key: 'name', label: 'Nom' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Telephone' },
            { key: 'specialty', label: 'Specialite' },
            { key: 'city', label: 'Ville' },
            { key: 'rating', label: 'Note' },
            { key: 'students_count', label: 'Etudiants suivis' },
          ]}
          emptyText="Aucun conseiller actif."
          rows={detailRows}
        />
      )
    }

    if (activeDetailView === 'pending') {
      return (
        <DetailTable
          columns={[
            { key: 'name', label: 'Nom' },
            { key: 'email', label: 'Email' },
            { key: 'role', label: 'Role' },
            { key: 'phone', label: 'Telephone' },
            { key: 'created_at', label: 'Date' },
            {
              key: 'actions',
              label: 'Actions',
              render: (row) => (
                <div className="flex flex-wrap gap-2">
                  <button className="rounded-md bg-emerald-600 px-3 py-1 text-xs font-black text-white" onClick={() => changeAccountStatus(row.id, 'actif')} type="button">Valider</button>
                  <button className="rounded-md bg-red-50 px-3 py-1 text-xs font-black text-red-700" onClick={() => changeAccountStatus(row.id, 'rejete')} type="button">Rejeter</button>
                </div>
              ),
            },
          ]}
          emptyText="Aucun compte en attente."
          rows={detailRows}
        />
      )
    }

    if (activeDetailView === 'tests') {
      return (
        <DetailTable
          columns={[
            { key: 'test', label: 'Test' },
            { key: 'student', label: 'Etudiant' },
            { key: 'email', label: 'Email' },
            { key: 'score', label: 'Score' },
            { key: 'completed_at', label: 'Date de passage' },
          ]}
          emptyText="Aucun test effectue."
          rows={detailRows}
        />
      )
    }

    if (activeDetailView === 'schools') {
      return (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-100 text-xs font-black uppercase text-slate-500">
              <tr>
                <th className="min-w-52 py-3 pr-4">Nom ecole</th>
                <th className="min-w-44 py-3 pr-4">Filieres</th>
                <th className="min-w-52 py-3 pr-4">Images de l'ecole</th>
                <th className="min-w-52 py-3 pr-4">Liens</th>
                <th className="min-w-36 py-3 pr-4">Statut</th>
                <th className="min-w-28 py-3 pr-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {detailRows.map((school) => {
                const draft = schoolEdits[school.id] ?? school

                return (
                  <tr key={school.id}>
                    <td className="py-3 pr-4">
                      <input className="min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateSchoolEdit(school.id, 'name', event.target.value)} value={draft.name ?? ''} />
                      <input className="mt-2 min-h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-bold text-slate-600" onChange={(event) => updateSchoolEdit(school.id, 'city', event.target.value)} placeholder="Ville" value={draft.city ?? ''} />
                    </td>
                    <td className="py-3 pr-4 font-bold text-slate-600">{school.filieres?.length ? school.filieres.join(', ') : 'Aucune filiere'}</td>
                    <td className="py-3 pr-4">
                      {school.image_url && (
                        <img alt="" className="mb-2 h-20 w-full rounded-md border border-slate-100 object-cover" src={school.image_url} />
                      )}
                      <input className="min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateSchoolEdit(school.id, 'image', event.target.value)} placeholder="URL ou chemin image" value={draft.image ?? ''} />
                    </td>
                    <td className="py-3 pr-4">
                      <input className="min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateSchoolEdit(school.id, 'website', event.target.value)} placeholder="https://..." value={draft.website ?? ''} />
                    </td>
                    <td className="py-3 pr-4">
                      <select className="min-h-10 rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateSchoolEdit(school.id, 'status', event.target.value)} value={draft.status ?? 'en_attente'}>
                        <option value="en_attente">En attente</option>
                        <option value="valide">Valide</option>
                        <option value="rejete">Rejete</option>
                      </select>
                    </td>
                    <td className="py-3 pr-4">
                      <button className="rounded-md bg-[#073f8f] px-3 py-2 text-xs font-black text-white" onClick={() => saveSchool(school.id)} type="button">Enregistrer</button>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {detailRows.length === 0 && (
            <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Aucune ecole enregistree.</p>
          )}
        </div>
      )
    }

    if (activeDetailView === 'logs') {
      return (
        <DetailTable
          columns={[
            { key: 'date', label: 'Date' },
            { key: 'user', label: 'Utilisateur' },
            { key: 'action', label: 'Action' },
            { key: 'detail', label: 'Detail' },
          ]}
          emptyText="Aucune action administrative enregistree."
          rows={detailRows}
        />
      )
    }

    return null
  }

  return (
    <section className="min-h-screen bg-slate-50">
      <div className="grid min-h-screen lg:grid-cols-[19rem_1fr]">
        {/* Sidebar fixe desktop: navigation de controle et rappel du compte admin connecte. */}
        <aside className="hidden bg-[#06265c] text-white lg:flex lg:flex-col">
          <div className="border-b border-white/10 px-6 py-6">
            <div className="flex items-center gap-3">
              <span className="grid h-12 w-12 place-items-center rounded-md border border-white/30 bg-white text-[#073071]">
                <AdminIcon className="h-7 w-7" name="graduation" />
              </span>
              <div>
                <p className="text-xl font-black leading-none">UNIVERSITY</p>
                <p className="text-xl font-black leading-none text-[#42c85a]">KEY</p>
                <p className="mt-1 text-xs text-blue-100">La cle de votre avenir</p>
              </div>
            </div>
          </div>

          <nav className="flex-1 overflow-y-auto px-4 py-5">
            <button
              className={`mb-5 flex min-h-12 w-full items-center gap-3 rounded-md px-4 text-left text-sm font-black ${activeSidebarItem === 'dashboard' ? 'bg-[#074fb2] text-white shadow-lg shadow-blue-950/20' : 'text-blue-50 hover:bg-white/10'}`}
              onClick={() => openSidebarItem({ id: 'dashboard', target: 'admin-overview' })}
              type="button"
            >
              <AdminIcon name="home" />
              {t('Tableau de bord')}
            </button>
            {sidebarGroups.map((group) => (
              <div className="mt-6" key={group.title}>
                <p className="px-3 text-xs font-black uppercase text-blue-200">{t(group.title)}</p>
                <div className="mt-2 space-y-1">
                  {group.items.map((item) => {
                    const badge = sidebarBadge(item)
                    const isActive = activeSidebarItem === item.id

                    return (
                      <button
                        className={`flex min-h-10 w-full items-center gap-3 rounded-md px-3 text-left text-sm font-bold transition ${isActive ? 'bg-[#074fb2] text-white shadow-lg shadow-blue-950/20' : 'text-blue-50 hover:bg-white/10'}`}
                        key={item.id}
                        onClick={() => openSidebarItem(item)}
                        type="button"
                      >
                        <AdminIcon className="h-5 w-5 shrink-0" name={item.icon} />
                        <span className="min-w-0 flex-1 truncate">{t(item.label)}</span>
                        {badge ? (
                          <span className={`grid h-5 min-w-5 place-items-center rounded-md px-1 text-[10px] font-black ${item.badge === 'pending_accounts' ? 'bg-orange-500 text-white' : item.badge === 'open_reports' ? 'bg-emerald-500 text-white' : 'bg-blue-100 text-[#06265c]'}`}>
                            {badge}
                          </span>
                        ) : null}
                      </button>
                    )
                  })}
                </div>
              </div>
            ))}
          </nav>

          <div className="border-t border-white/10 p-5">
            <div className="flex items-center gap-3">
              <span className="grid h-12 w-12 place-items-center rounded-full bg-white text-sm font-black text-[#06265c]">{initials(dashboard.admin.name)}</span>
              <div>
                <p className="text-sm font-black">{dashboard.admin.name}</p>
                <p className="text-xs text-blue-100">{dashboard.admin.role_label}</p>
                <p className="mt-1 text-xs font-black text-emerald-300">En ligne</p>
              </div>
            </div>
          </div>
        </aside>

        <main className="min-w-0">
          {/* Header operationnel: recherche globale, notifications et identite admin. */}
          <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
            <div className="mx-auto flex max-w-[100rem] items-center justify-between gap-4">
              <div className="relative hidden w-full max-w-xl md:block">
                <div className="flex min-h-11 items-center gap-3 rounded-md border border-slate-200 bg-white px-4 text-slate-400">
                  <AdminIcon name="search" />
                  <input
                    className="w-full border-0 text-sm text-slate-900 outline-none placeholder:text-slate-400"
                    onBlur={() => window.setTimeout(() => setIsSearchOpen(false), 150)}
                    onChange={(event) => {
                      setSearchQuery(event.target.value)
                      setIsSearchOpen(true)
                    }}
                    onFocus={() => setIsSearchOpen(true)}
                    placeholder={t('Rechercher une ecole, une ville, une filiere...')}
                    value={searchQuery}
                  />
                </div>

                {isSearchOpen && searchQuery.trim().length >= 2 && (
                  <div className="absolute left-0 right-0 top-[3.25rem] z-50 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl shadow-slate-200">
                    {searchStatus === 'loading' && (
                      <p className="px-4 py-3 text-sm font-bold text-slate-500">Recherche en cours...</p>
                    )}

                    {searchStatus === 'error' && (
                      <p className="px-4 py-3 text-sm font-bold text-red-600">Recherche indisponible pour le moment.</p>
                    )}

                    {searchStatus === 'ready' && searchResults.length === 0 && (
                      <p className="px-4 py-3 text-sm font-bold text-slate-500">Aucun etablissement ou filiere trouve.</p>
                    )}

                    {searchResults.map((result) => (
                      <a
                        className="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-blue-50"
                        href={result.url}
                        key={result.id}
                        onClick={() => {
                          openSearchResult(result)
                        }}
                      >
                        <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-md bg-blue-50 text-[#074fb2]">
                          <AdminIcon className="h-5 w-5" name="school" />
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
              </div>
              <div className="ml-auto flex items-center gap-4">
                <button className="relative grid h-10 w-10 place-items-center rounded-md text-[#061d49] hover:bg-blue-50" type="button">
                  <AdminIcon name="bell" />
                  <span className="absolute right-1 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white">12</span>
                </button>
                <button className="relative grid h-10 w-10 place-items-center rounded-md text-[#061d49] hover:bg-blue-50" type="button">
                  <AdminIcon name="mail" />
                  <span className="absolute right-1 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white">8</span>
                </button>
                <span className="grid h-11 w-11 place-items-center rounded-full bg-[#061d49] text-sm font-black text-white">{initials(dashboard.admin.name)}</span>
                <div className="hidden sm:block">
                  <p className="text-sm font-black text-[#061d49]">Admin University Key</p>
                  <p className="text-xs font-bold text-slate-500">{dashboard.admin.role_label}</p>
                </div>
              </div>
            </div>
          </header>

          <div className="mx-auto max-w-[100rem] px-4 py-6 sm:px-6 lg:px-8" id="admin-overview">
            {status.state === 'error' && (
              <p className="mb-5 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{status.message}</p>
            )}

            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <h1 className="text-3xl font-black text-[#061d49]">{t('Bonjour Administrateur')}</h1>
                <p className="mt-1 text-sm font-bold text-slate-500">{t('Vue globale de la plateforme University Key')}</p>
              </div>
              <div className="flex flex-wrap gap-3">
                <span className="inline-flex min-h-11 items-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm font-black text-[#061d49]">
                  <AdminIcon name="calendar" />
                  17 Juin 2026
                </span>
                <button className="inline-flex min-h-11 items-center gap-2 rounded-md bg-[#073f8f] px-5 text-sm font-black text-white" type="button">
                  <AdminIcon name="download" />
                  Exporter le rapport
                </button>
              </div>
            </div>

            {/* Indicateurs alimentes par l'API admin: utilisateurs, tests, ecoles et signalements. */}
            <section className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
              <ClickableMetricCard active={activeDetailView === 'students'} icon="users" label={t('Etudiants inscrits')} onClick={() => loadDetailView('students')} tone="bg-blue-50 text-blue-700" trend={t('Cliquer pour voir la liste')} value={metrics.students} />
              <ClickableMetricCard active={activeDetailView === 'counselors'} icon="users" label={t('Conseillers actifs')} onClick={() => loadDetailView('counselors')} tone="bg-emerald-50 text-emerald-700" trend={t('Cliquer pour voir la liste')} value={metrics.active_counselors} />
              <ClickableMetricCard active={activeDetailView === 'pending'} icon="shield" label={t('Comptes en attente')} onClick={() => loadDetailView('pending')} tone="bg-orange-50 text-orange-700" trend={t('Cliquer pour valider')} value={metrics.pending_accounts} />
              <ClickableMetricCard active={activeDetailView === 'tests'} icon="clipboard" label={t('Tests effectues')} onClick={() => loadDetailView('tests')} tone="bg-violet-50 text-violet-700" trend={t('Cliquer pour voir les passages')} value={metrics.tests_completed} />
              <ClickableMetricCard active={activeDetailView === 'schools'} icon="school" label={t('Ecoles enregistrees')} onClick={() => loadDetailView('schools')} tone="bg-blue-50 text-blue-700" trend={t('Cliquer pour modifier')} value={metrics.schools} />
              <MetricCard icon="flag" label={t('Signalements ouverts')} tone="bg-red-50 text-red-700" trend={t('a surveiller')} value={metrics.open_reports} />
            </section>

            {activeDetailView && (
              <section className="mt-5 scroll-mt-24" id="admin-detail-view">
                <Panel title={detailViews[activeDetailView]?.title ?? 'Details'}>
                  {detailStatus.message && (
                    <p className={`mb-4 rounded-md px-4 py-3 text-sm font-bold ${detailStatus.state === 'error' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`}>
                      {detailStatus.message}
                    </p>
                  )}
                  {renderDetailContent()}
                </Panel>
              </section>
            )}

            {isTestManagerOpen && (
              <section className="mt-5 scroll-mt-24" id="admin-tests">
                <Panel title="Gestion des tests d'orientation">
                <div className="grid gap-6 xl:grid-cols-[0.9fr_1.4fr]">
                  <form className="grid gap-4" onSubmit={handleTestSubmit}>
                    <div>
                      <label className="text-xs font-black text-slate-500" htmlFor="test-title">Titre du test</label>
                      <input
                        className="mt-2 min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49] outline-none focus:border-[#074fb2] focus:ring-4 focus:ring-blue-100"
                        id="test-title"
                        onChange={(event) => setTestForm((current) => ({ ...current, titre: event.target.value }))}
                        required
                        value={testForm.titre}
                      />
                    </div>

                    <div>
                      <label className="text-xs font-black text-slate-500" htmlFor="test-description">Description</label>
                      <textarea
                        className="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-bold text-[#061d49] outline-none focus:border-[#074fb2] focus:ring-4 focus:ring-blue-100"
                        id="test-description"
                        onChange={(event) => setTestForm((current) => ({ ...current, description: event.target.value }))}
                        value={testForm.description}
                      />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                      <label className="block">
                        <span className="text-xs font-black text-slate-500">Langue</span>
                        <select className="mt-2 min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => setTestForm((current) => ({ ...current, langue: event.target.value }))} value={testForm.langue}>
                          <option value="fr">FR</option>
                          <option value="en">EN</option>
                        </select>
                      </label>
                      <label className="block">
                        <span className="text-xs font-black text-slate-500">Duree</span>
                        <input className="mt-2 min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" min="1" onChange={(event) => setTestForm((current) => ({ ...current, duree_minutes: event.target.value }))} type="number" value={testForm.duree_minutes} />
                      </label>
                      <label className="block">
                        <span className="text-xs font-black text-slate-500">Statut</span>
                        <select className="mt-2 min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => setTestForm((current) => ({ ...current, statut: event.target.value }))} value={testForm.statut}>
                          <option value="brouillon">Brouillon</option>
                          <option value="publie">Publie</option>
                          <option value="archive">Archive</option>
                        </select>
                      </label>
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                      <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <h3 className="text-sm font-black text-[#061d49]">Questions et algorithme</h3>
                          <p className="mt-1 text-xs font-bold text-slate-500">Chaque choix peut modifier les scores sci, tech, com, sante et lit.</p>
                        </div>
                        <button className="min-h-10 rounded-md bg-white px-4 text-xs font-black text-[#074fb2] shadow-sm" onClick={addQuestion} type="button">
                          Ajouter une question
                        </button>
                      </div>

                      <div className="mt-4 space-y-4">
                        {testQuestions.map((question, questionIndex) => (
                          <article className="rounded-lg border border-slate-200 bg-white p-4" key={`question-${questionIndex}`}>
                            <div className="grid gap-3 lg:grid-cols-[4rem_1fr_auto]">
                              <label className="block">
                                <span className="text-xs font-black text-slate-500">Ordre</span>
                                <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-2 text-sm font-bold text-[#061d49]" min="1" onChange={(event) => updateQuestion(questionIndex, 'ordre', event.target.value)} type="number" value={question.ordre} />
                              </label>
                              <label className="block">
                                <span className="text-xs font-black text-slate-500">Libelle de la question</span>
                                <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateQuestion(questionIndex, 'libelle', event.target.value)} value={question.libelle} />
                              </label>
                              <button className="self-end rounded-md border border-red-100 px-3 py-2 text-xs font-black text-red-600" onClick={() => removeQuestion(questionIndex)} type="button">
                                Supprimer
                              </button>
                            </div>

                            <div className="mt-3 grid gap-3 sm:grid-cols-3">
                              <label className="block">
                                <span className="text-xs font-black text-slate-500">Type</span>
                                <select className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateQuestion(questionIndex, 'type', event.target.value)} value={question.type}>
                                  <option value="choix_unique">Choix unique</option>
                                  <option value="choix_multiple">Choix multiple</option>
                                  <option value="echelle">Echelle</option>
                                </select>
                              </label>
                              <label className="block">
                                <span className="text-xs font-black text-slate-500">Domaine</span>
                                <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateQuestion(questionIndex, 'domaine', event.target.value)} value={question.domaine} />
                              </label>
                              <label className="mt-7 flex items-center gap-2 text-sm font-black text-[#061d49]">
                                <input checked={question.active} onChange={(event) => updateQuestion(questionIndex, 'active', event.target.checked)} type="checkbox" />
                                Active
                              </label>
                            </div>

                            <div className="mt-4 space-y-3">
                              {question.choices.map((choice, choiceIndex) => (
                                <div className="rounded-md border border-slate-100 bg-slate-50 p-3" key={`choice-${questionIndex}-${choiceIndex}`}>
                                  <div className="grid gap-3 lg:grid-cols-[3rem_1fr_4rem_auto]">
                                    <label className="block">
                                      <span className="text-xs font-black text-slate-500">Ordre</span>
                                      <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-2 text-sm font-bold text-[#061d49]" min="1" onChange={(event) => updateChoice(questionIndex, choiceIndex, 'ordre', event.target.value)} type="number" value={choice.ordre} />
                                    </label>
                                    <label className="block">
                                      <span className="text-xs font-black text-slate-500">Choix de reponse</span>
                                      <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-3 text-sm font-bold text-[#061d49]" onChange={(event) => updateChoice(questionIndex, choiceIndex, 'libelle', event.target.value)} value={choice.libelle} />
                                    </label>
                                    <label className="block">
                                      <span className="text-xs font-black text-slate-500">Valeur</span>
                                      <input className="mt-2 min-h-10 w-full rounded-md border border-slate-200 px-2 text-sm font-bold text-[#061d49]" onChange={(event) => updateChoice(questionIndex, choiceIndex, 'valeur', event.target.value)} type="number" value={choice.valeur} />
                                    </label>
                                    <button className="self-end rounded-md border border-red-100 px-3 py-2 text-xs font-black text-red-600" onClick={() => removeChoice(questionIndex, choiceIndex)} type="button">
                                      Supprimer
                                    </button>
                                  </div>

                                  <div className="mt-3 grid gap-2 sm:grid-cols-5">
                                    {weightFields.map(([slug, label]) => (
                                      <label className="block" key={slug}>
                                        <span className="text-xs font-black text-slate-500">{label}</span>
                                        <input className="mt-1 min-h-9 w-full rounded-md border border-slate-200 px-2 text-sm font-bold text-[#061d49]" min="0" onChange={(event) => updateChoiceWeight(questionIndex, choiceIndex, slug, event.target.value)} step="0.5" type="number" value={choice.weights?.[slug] ?? 0} />
                                      </label>
                                    ))}
                                  </div>

                                  <div className="mt-3">
                                    <p className="text-xs font-black text-slate-500">Axes personnalite</p>
                                    <div className="mt-2 grid gap-2 sm:grid-cols-4">
                                      {axisFields.map(([slug, label]) => (
                                        <label className="block" key={slug}>
                                          <span className="text-xs font-black text-slate-500">{label}</span>
                                          <input className="mt-1 min-h-9 w-full rounded-md border border-slate-200 px-2 text-sm font-bold text-[#061d49]" min="0" onChange={(event) => updateChoiceAxis(questionIndex, choiceIndex, slug, event.target.value)} step="0.5" type="number" value={choice.metadata?.axes?.[slug] ?? 0} />
                                        </label>
                                      ))}
                                    </div>
                                  </div>
                                </div>
                              ))}

                              <button className="min-h-10 rounded-md border border-slate-200 px-4 text-xs font-black text-[#074fb2]" onClick={() => addChoice(questionIndex)} type="button">
                                Ajouter un choix
                              </button>
                            </div>
                          </article>
                        ))}

                        {testQuestions.length === 0 && (
                          <p className="rounded-md bg-white px-4 py-3 text-sm font-bold text-slate-500">
                            Aucune question chargee. Ajoute une question ou clique sur modifier pour charger un test existant.
                          </p>
                        )}
                      </div>
                    </div>

                    {testStatus.message && (
                      <p className={`rounded-md px-4 py-3 text-sm font-bold ${testStatus.state === 'error' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`}>
                        {testStatus.message}
                      </p>
                    )}

                    <div className="flex flex-wrap gap-3">
                      <button className="inline-flex min-h-11 items-center gap-2 rounded-md bg-[#073f8f] px-5 text-sm font-black text-white disabled:opacity-60" disabled={testStatus.state === 'loading'} type="submit">
                        <AdminIcon className="h-4 w-4" name="clipboard" />
                        {editingTestId ? 'Modifier le test' : 'Ajouter le test'}
                      </button>
                      {editingTestId && (
                        <button className="min-h-11 rounded-md border border-slate-200 px-5 text-sm font-black text-[#061d49]" onClick={resetTestForm} type="button">
                          Annuler
                        </button>
                      )}
                    </div>
                  </form>

                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead className="border-b border-slate-100 text-xs font-black text-slate-500">
                        <tr>
                          <th className="py-3">Test</th>
                          <th className="py-3">Statut</th>
                          <th className="py-3">Questions</th>
                          <th className="py-3">Passages</th>
                          <th className="py-3">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {tests.map((test) => (
                          <tr key={test.id}>
                            <td className="max-w-xs py-3">
                              <p className="truncate font-black text-[#061d49]">{test.title}</p>
                              <p className="truncate text-xs font-bold text-slate-500">{test.description || 'Aucune description'}</p>
                            </td>
                            <td className="py-3">
                              <span className={`rounded-md px-2 py-1 text-xs font-black ${test.status === 'publie' ? 'bg-emerald-50 text-emerald-700' : test.status === 'archive' ? 'bg-slate-100 text-slate-600' : 'bg-orange-50 text-orange-700'}`}>
                                {test.status}
                              </span>
                            </td>
                            <td className="py-3 font-bold text-slate-600">{formatNumber(test.questions_count)}</td>
                            <td className="py-3 font-bold text-slate-600">{formatNumber(test.sessions_count)}</td>
                            <td className="py-3">
                              <div className="flex gap-2">
                                <button className="rounded-md border border-slate-200 px-3 py-1 text-xs font-black text-[#074fb2]" onClick={() => startEditTest(test)} type="button">Modifier</button>
                                <button className="rounded-md border border-red-100 px-3 py-1 text-xs font-black text-red-600" onClick={() => deleteTest(test)} type="button">Supprimer</button>
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                    {tests.length === 0 && (
                      <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Aucun test disponible.</p>
                    )}
                  </div>
                </div>
                </Panel>
              </section>
            )}

            {isLegalManagerOpen && (
              <section className="mt-5 scroll-mt-24" id="admin-legal-rules">
                <Panel title={t('Confidentialite et consentements')}>
                  <form className="grid gap-5" onSubmit={saveLegalRules}>
                    <div className="rounded-md bg-blue-50 px-4 py-3 text-sm font-bold text-[#073f8f]">
                      {t('Ces textes sont publies dans les boutons de consentement a l inscription et dans le pied de page. La syntaxe Markdown simple (#, ## et *) est prise en charge.')}
                    </div>
                    {legalStatus.message && (
                      <p className={`rounded-md px-4 py-3 text-sm font-bold ${legalStatus.state === 'error' ? 'bg-red-50 text-red-700' : legalStatus.state === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-600'}`}>
                        {legalStatus.message}
                      </p>
                    )}
                    <label className="block">
                      <span className="text-sm font-black text-[#061d49]">{t('Consentement et conditions d utilisation')}</span>
                      <span className="mt-1 block text-xs font-bold text-slate-500">{t('Texte accepte par l utilisateur lors de la creation de son compte.')}</span>
                      <textarea
                        className="mt-3 min-h-72 w-full rounded-md border border-slate-200 px-4 py-3 font-mono text-sm leading-6 text-slate-800 outline-none focus:border-[#074fb2] focus:ring-4 focus:ring-blue-100"
                        onChange={(event) => setLegalRules((current) => ({ ...current, conditions: event.target.value }))}
                        required
                        value={legalRules.conditions}
                      />
                    </label>
                    <label className="block">
                      <span className="text-sm font-black text-[#061d49]">{t('Politique de confidentialite')}</span>
                      <span className="mt-1 block text-xs font-bold text-slate-500">{t('Texte affiche par le bouton Confidentialite et dans le pied de page.')}</span>
                      <textarea
                        className="mt-3 min-h-72 w-full rounded-md border border-slate-200 px-4 py-3 font-mono text-sm leading-6 text-slate-800 outline-none focus:border-[#074fb2] focus:ring-4 focus:ring-blue-100"
                        onChange={(event) => setLegalRules((current) => ({ ...current, politique: event.target.value }))}
                        required
                        value={legalRules.politique}
                      />
                    </label>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                      <p className="text-xs font-bold text-slate-500">{t('Derniere publication :')} {legalRules.updated_at ? formatDate(legalRules.updated_at) : t('non disponible')}</p>
                      <button className="min-h-11 rounded-md bg-[#073f8f] px-5 text-sm font-black text-white disabled:opacity-60" disabled={legalStatus.state === 'loading'} type="submit">
                        {legalStatus.state === 'loading' ? t('Publication en cours...') : t('Publier les modifications')}
                      </button>
                    </div>
                  </form>
                </Panel>
              </section>
            )}

            <div className="mt-5 grid gap-5 xl:grid-cols-[1.2fr_0.8fr_0.85fr]">
              <Panel title="Evolution des inscriptions">
                {/* Graphique simple en CSS en attendant une bibliotheque de charting. */}
                <div className="h-64 rounded-md border border-slate-100 bg-gradient-to-b from-white to-blue-50 px-5 py-4">
                  <div className="flex h-full items-end gap-4">
                    {[42, 50, 58, 67, 74, 82].map((height, index) => (
                      <div className="flex flex-1 flex-col items-center gap-2" key={height}>
                        <div className="flex w-full items-end gap-1">
                          <span className="block w-1/2 rounded-t bg-[#2563eb]" style={{ height: `${height}%` }} />
                          <span className="block w-1/2 rounded-t bg-[#22a447]" style={{ height: `${Math.max(height - 25, 12)}%` }} />
                        </div>
                        <span className="text-xs font-bold text-slate-500">{['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin'][index]}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </Panel>

              <Panel title="Activites recentes">
                <div className="space-y-4">
                  {dashboard.recent_activity.map((activity) => (
                    <article className="flex gap-3 border-b border-slate-100 pb-3 last:border-b-0" key={activity.id}>
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 text-[#074fb2]">
                        <AdminIcon className="h-5 w-5" name="shield" />
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-black text-[#061d49]">{activity.title}</p>
                        <p className="text-xs font-bold text-slate-500">{activity.time}</p>
                      </div>
                    </article>
                  ))}
                </div>
              </Panel>

              <Panel action="Voir toutes" title="Repartition des utilisateurs">
                <div className="space-y-4">
                  {dashboard.user_distribution.map((item) => (
                    <div className="grid grid-cols-[1fr_auto] gap-3" key={item.label}>
                      <div>
                        <div className="flex justify-between gap-2 text-sm font-black text-[#061d49]">
                          <span>{item.label}</span>
                          <span>{item.percentage}%</span>
                        </div>
                        <div className="mt-2 h-2 rounded-full bg-slate-100">
                          <span className={`block h-2 rounded-full ${item.color}`} style={{ width: `${item.percentage}%` }} />
                        </div>
                      </div>
                      <span className="text-sm font-bold text-slate-500">{formatNumber(item.value)}</span>
                    </div>
                  ))}
                </div>
              </Panel>
            </div>

            {(activeDetailView === 'pending' || isTestManagerOpen || ['reports', 'database', 'security'].includes(activeSidebarItem)) && (
              <div className="mt-5 grid gap-5 xl:grid-cols-[1fr_0.7fr_0.75fr]">
              {activeDetailView === 'pending' && (
                <Panel action="Voir tout" title="Comptes en attente de validation">
                <span id="admin-pending" className="block scroll-mt-24" />
                {/* Tableau de validation: vide tant qu'aucun compte n'a le statut en_attente. */}
                {dashboard.pending_accounts.length === 0 ? (
                  <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Aucun compte en attente.</p>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead className="text-xs font-black text-slate-500">
                        <tr>
                          <th className="py-2">Nom</th>
                          <th className="py-2">Role</th>
                          <th className="py-2">Type</th>
                          <th className="py-2">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {dashboard.pending_accounts.map((account) => (
                          <tr key={account.id}>
                            <td className="py-3 font-black text-[#061d49]">{account.name}</td>
                            <td className="py-3 text-slate-600">{account.role}</td>
                            <td className="py-3 text-slate-600">{account.type}</td>
                            <td className="py-3">
                              <button className="rounded-md border border-slate-200 px-3 py-1 text-xs font-black text-[#074fb2]" type="button">Voir</button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
                </Panel>
              )}

              {isTestManagerOpen && (
                <Panel action="Voir tout" title="Tests les plus populaires">
                <span id="admin-popular-tests" className="block scroll-mt-24" />
                {dashboard.popular_tests.length === 0 ? (
                  <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Aucun test effectue pour le moment.</p>
                ) : (
                  <div className="space-y-4">
                    {dashboard.popular_tests.map((test) => (
                      <article className="grid gap-2" key={test.title}>
                        <div className="flex justify-between text-sm">
                          <span className="font-black text-[#061d49]">{test.title}</span>
                          <span className="font-bold text-slate-500">{formatNumber(test.passages)}</span>
                        </div>
                        <div className="h-2 rounded-full bg-slate-100">
                          <span className="block h-2 rounded-full bg-emerald-600" style={{ width: `${Math.min(test.average_score, 100)}%` }} />
                        </div>
                      </article>
                    ))}
                  </div>
                )}
                </Panel>
              )}

              {['reports', 'database', 'security'].includes(activeSidebarItem) && (
                <Panel title="Alertes systeme">
                <span id="admin-alerts" className="block scroll-mt-24" />
                {/* Alertes synthetiques pour l'etat de securite et de maintenance. */}
                <div className="space-y-3">
                  {dashboard.system_alerts.map((alert) => (
                    <article className="flex items-center gap-3 border-b border-slate-100 pb-3 last:border-b-0" key={alert.title}>
                      <span className={`grid h-9 w-9 place-items-center rounded-full ${alert.tone === 'green' ? 'bg-emerald-50 text-emerald-700' : alert.tone === 'orange' ? 'bg-orange-50 text-orange-700' : 'bg-blue-50 text-blue-700'}`}>
                        <AdminIcon className="h-5 w-5" name={alert.tone === 'orange' ? 'flag' : 'shield'} />
                      </span>
                      <div>
                        <p className="text-sm font-black text-[#061d49]">{alert.title}</p>
                        <p className="text-xs font-bold text-slate-500">{alert.detail}</p>
                      </div>
                    </article>
                  ))}
                </div>
                </Panel>
              )}
              </div>
            )}
          </div>
        </main>
      </div>
    </section>
  )
}
