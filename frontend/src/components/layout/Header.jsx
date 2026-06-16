/**
 * Affiche l'en-tete principal avec navigation, changement de langue et action de connexion.
 */
export function Header({ language, labels, onLanguageChange }) {
  return (
    <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm shadow-slate-200/40 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
        {/* Identite visuelle: logo bouclier + nom de marque en deux couleurs. */}
        <a className="focus-ring flex items-center gap-3 rounded-md" href="#home">
          <span className="grid h-11 w-11 place-items-center rounded-md border border-[#0a3c86]/20 bg-white text-[#073071] shadow-sm">
            <svg aria-hidden="true" className="h-8 w-8" viewBox="0 0 32 32" fill="none">
              <path d="M16 3 27 7.2v8.2c0 6.5-4.4 10.8-11 13.6C9.4 26.2 5 21.9 5 15.4V7.2L16 3Z" stroke="currentColor" strokeWidth="1.7" />
              <path d="m10 13 6-3.2 6 3.2-6 3.2-6-3.2Z" fill="#2fa34a" />
              <path d="M12.2 16.2v3.2c2.5 1.4 5.1 1.4 7.6 0v-3.2" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
            </svg>
          </span>
          <span className="leading-none">
            <span className="block text-lg font-black tracking-normal text-[#06255a]">UNIVERSITY</span>
            <span className="block text-lg font-black tracking-normal text-[#2fa34a]">KEY</span>
            <span className="mt-1 block text-[10px] font-medium text-slate-500">{labels.heroEyebrow}</span>
          </span>
        </a>

        {/* Navigation desktop cachee sur petits ecrans pour garder le header lisible. */}
        <nav aria-label="Navigation principale" className="hidden items-center gap-2 lg:flex">
          {labels.nav.map((item) => (
            <a
              className="focus-ring rounded-md px-3 py-2 text-sm font-extrabold text-[#061d49] hover:bg-blue-50"
              href={item.href}
              key={item.href}
            >
              {item.label}
            </a>
          ))}
        </nav>

        {/* Actions rapides: langue, connexion et inscription. */}
        <div className="flex items-center gap-2">
          <button
            aria-label="Changer la langue"
            className="focus-ring hidden rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-800 sm:inline-flex"
            onClick={() => onLanguageChange(language === 'fr' ? 'en' : 'fr')}
            type="button"
          >
            {language === 'fr' ? 'EN' : 'FR'}
          </button>
          <a
            className="focus-ring rounded-md px-3 py-2 text-sm font-extrabold text-[#073071] hover:bg-blue-50"
            href="#connexion"
          >
            {labels.login}
          </a>
          <a
            className="focus-ring rounded-md bg-[#073f8f] px-4 py-3 text-sm font-extrabold text-white shadow-sm shadow-blue-900/20 hover:bg-[#052f6f]"
            href="#inscription"
          >
            {labels.register}
          </a>
        </div>
      </div>
    </header>
  )
}
