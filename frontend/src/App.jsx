import { useEffect, useState } from 'react'
import './App.css'
import { Footer } from './components/layout/Footer'
import { Header } from './components/layout/Header'
import { LegalModal } from './components/legal/LegalModal'
import { useLocalizedCopy } from './hooks/useLocalizedCopy'
import { LoginPage } from './pages/auth/LoginPage'
import { RegisterPage } from './pages/auth/RegisterPage'
import { AdminDashboardPage } from './pages/admin/AdminDashboardPage'
import { CounselorDashboardPage } from './pages/counselor/CounselorDashboardPage'
import { EtablissementDetailPage } from './pages/public/EtablissementDetailPage'
import { HomePage } from './pages/public/HomePage'
import { StudentDashboardPage } from './pages/student/StudentDashboardPage'

// Convertit le hash courant en page interne de la SPA.
function getActivePage() {
  const hash = window.location.hash.replace('#', '')

  if (['connexion', 'login', 'auth'].includes(hash)) {
    return 'login'
  }

  if (['inscription', 'register'].includes(hash)) {
    return 'register'
  }

  if (['dashboard', 'tableau-de-bord', 'student-dashboard'].includes(hash)) {
    return 'student-dashboard'
  }

  if (['admin', 'admin-dashboard', 'administration'].includes(hash)) {
    return 'admin-dashboard'
  }

  if (['conseiller', 'counselor', 'counselor-dashboard'].includes(hash)) {
    return 'counselor-dashboard'
  }

  if (/^etablissement-\d+$/.test(hash)) {
    return 'etablissement-detail'
  }

  return 'home'
}

// Extrait l'identifiant de la fiche etablissement depuis une ancre #etablissement-12.
function getActiveEtablissementId() {
  const match = window.location.hash.replace('#', '').match(/^etablissement-(\d+)$/)

  return match ? Number(match[1]) : null
}

/**
 * Assemble les premieres pages essentielles de University Key dans une experience SPA.
 */
function App() {
  // La langue est globale et persistante: le bouton FR/EN impacte toutes les pages.
  const [language, setLanguage] = useState(() => {
    const savedLanguage = window.localStorage.getItem('university_key_language')
    return ['fr', 'en'].includes(savedLanguage) ? savedLanguage : 'fr'
  })
  // activePage suit le hash pour eviter un routeur complet dans cette premiere version.
  const [activePage, setActivePage] = useState(getActivePage)
  const [legalModalType, setLegalModalType] = useState(null)
  const labels = useLocalizedCopy(language)

  useEffect(() => {
    // Synchronise l'interface avec la navigation par ancres (#connexion, #dashboard, etc.).
    const handleHashChange = () => setActivePage(getActivePage())

    window.addEventListener('hashchange', handleHashChange)
    return () => window.removeEventListener('hashchange', handleHashChange)
  }, [])

  useEffect(() => {
    // Persiste le choix utilisateur et expose la langue au navigateur/accessibilite.
    window.localStorage.setItem('university_key_language', language)
    document.documentElement.lang = language
  }, [language])

  const isAuthPage = activePage === 'login' || activePage === 'register'
  const isAdminDashboard = activePage === 'admin-dashboard'

  return (
    <>
      <a className="skip-link" href="#main">
        {labels.skip}
      </a>
      <Header labels={labels} language={language} onLanguageChange={setLanguage} showAuthActions={!isAdminDashboard} />
      <main id="main">
        {activePage === 'login' && <LoginPage labels={labels.auth} />}
        {activePage === 'register' && <RegisterPage labels={labels.auth} onOpenLegal={setLegalModalType} />}
        {activePage === 'admin-dashboard' && <AdminDashboardPage />}
        {activePage === 'counselor-dashboard' && <CounselorDashboardPage labels={labels.counselorDashboard} />}
        {activePage === 'student-dashboard' && <StudentDashboardPage labels={labels.dashboard} />}
        {activePage === 'etablissement-detail' && <EtablissementDetailPage etablissementId={getActiveEtablissementId()} />}
        {activePage === 'home' && <HomePage labels={labels} />}
      </main>
      <Footer labels={labels} onOpenLegal={setLegalModalType} variant={isAuthPage ? 'dark' : 'light'} />
      <LegalModal labels={labels.legalModal} onClose={() => setLegalModalType(null)} type={legalModalType} />
    </>
  )
}

export default App
