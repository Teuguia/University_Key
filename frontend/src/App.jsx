import { useEffect, useState } from 'react'
import './App.css'
import { Footer } from './components/layout/Footer'
import { Header } from './components/layout/Header'
import { LegalModal } from './components/legal/LegalModal'
import { useLocalizedCopy } from './hooks/useLocalizedCopy'
import { LoginPage } from './pages/auth/LoginPage'
import { RegisterPage } from './pages/auth/RegisterPage'
import { HomePage } from './pages/public/HomePage'

function getActivePage() {
  const hash = window.location.hash.replace('#', '')

  if (['connexion', 'login', 'auth'].includes(hash)) {
    return 'login'
  }

  if (['inscription', 'register'].includes(hash)) {
    return 'register'
  }

  return 'home'
}

/**
 * Assemble les premieres pages essentielles de University Key dans une experience SPA.
 */
function App() {
  const [language, setLanguage] = useState('fr')
  const [activePage, setActivePage] = useState(getActivePage)
  const [legalModalType, setLegalModalType] = useState(null)
  const labels = useLocalizedCopy(language)

  useEffect(() => {
    const handleHashChange = () => setActivePage(getActivePage())

    window.addEventListener('hashchange', handleHashChange)
    return () => window.removeEventListener('hashchange', handleHashChange)
  }, [])

  const isAuthPage = activePage === 'login' || activePage === 'register'

  return (
    <>
      <a className="skip-link" href="#main">
        {labels.skip}
      </a>
      <Header labels={labels} language={language} onLanguageChange={setLanguage} />
      <main id="main">
        {activePage === 'login' && <LoginPage />}
        {activePage === 'register' && <RegisterPage onOpenLegal={setLegalModalType} />}
        {activePage === 'home' && <HomePage labels={labels} />}
      </main>
      <Footer labels={labels} onOpenLegal={setLegalModalType} variant={isAuthPage ? 'dark' : 'light'} />
      <LegalModal onClose={() => setLegalModalType(null)} type={legalModalType} />
    </>
  )
}

export default App
