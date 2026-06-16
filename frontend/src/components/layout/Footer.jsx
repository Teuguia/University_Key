import { BrandIcon } from '../common/BrandIcon'

/**
 * Affiche le pied de page avec une variante claire pour la landing
 * et une variante sombre pour les pages d'authentification.
 */
export function Footer({ labels, onOpenLegal, variant = 'light' }) {
  const isDark = variant === 'dark'
  const footerClassName = isDark
    ? 'bg-gradient-to-r from-[#06265c] to-[#00468f] px-4 pb-8 pt-10 text-sm text-blue-50 sm:px-6 lg:px-8'
    : 'bg-white px-4 pb-8 pt-10 text-sm text-slate-600 sm:px-6 lg:px-8'
  const mutedText = isDark ? 'text-blue-100' : 'text-slate-500'
  const titleText = isDark ? 'text-white' : 'text-[#061d49]'
  const legalBorder = isDark ? 'border-white/10' : 'border-slate-100'

  const footerColumns = [
    ['Plateforme', [{ label: 'Accueil' }, { label: 'Explorer' }, { label: 'Filières' }, { label: 'Écoles' }, { label: 'Conseillers' }]],
    ['Ressources', [{ label: 'Blog' }, { label: 'Guides' }, { label: "Conseils d'orientation" }, { label: 'Bourses' }, { label: 'FAQ' }]],
    ['Entreprise', [{ label: 'À propos' }, { label: 'Contact' }, { label: 'Devenir conseiller' }, { label: 'Partenaires' }, { label: 'Carrières' }]],
    ['Légal', [{ label: "Conditions d'utilisation", legalType: 'conditions' }, { label: 'Politique de confidentialité', legalType: 'politique' }, { label: 'Mentions légales' }]],
  ]

  return (
    <footer className={footerClassName}>
      <div className="mx-auto grid max-w-7xl gap-8 md:grid-cols-[1.5fr_1fr_1fr_1fr_1fr]">
        <div>
          {/* Rappel de marque dans le footer pour terminer la page avec la meme identite. */}
          <div className="flex items-center gap-3">
            <span className="grid h-10 w-10 place-items-center rounded-md border border-white/30 bg-white text-[#073071]">
              <svg aria-hidden="true" className="h-7 w-7" viewBox="0 0 32 32" fill="none">
                <path d="M16 3 27 7.2v8.2c0 6.5-4.4 10.8-11 13.6C9.4 26.2 5 21.9 5 15.4V7.2L16 3Z" stroke="currentColor" strokeWidth="1.7" />
                <path d="m10 13 6-3.2 6 3.2-6 3.2-6-3.2Z" fill="#2fa34a" />
              </svg>
            </span>
            <span className="leading-none">
              <span className={`block text-base font-black ${isDark ? 'text-white' : 'text-[#06255a]'}`}>UNIVERSITY</span>
              <span className="block text-base font-black text-[#2fa34a]">KEY</span>
              <span className={`mt-1 block text-[10px] ${mutedText}`}>{labels.heroEyebrow}</span>
            </span>
          </div>

          <p className={`mt-5 max-w-xs text-xs leading-6 ${mutedText}`}>
            University Key est la plateforme d'orientation académique et professionnelle qui vous aide à faire les bons choix pour votre avenir.
          </p>

          {/* Liens sociaux placeholders en attendant les vraies URLs. */}
          <div className="mt-6 flex gap-3">
            {[
              ['facebook', 'Facebook'],
              ['instagram', 'Instagram'],
              ['twitter', 'Twitter'],
            ].map(([name, label]) => (
              <a
                aria-label={label}
                className={`focus-ring grid h-8 w-8 place-items-center rounded-md border bg-white ${isDark ? 'border-white/30' : 'border-blue-200'}`}
                href="#about"
                key={name}
              >
                <BrandIcon className="h-5 w-5" name={name} />
              </a>
            ))}
          </div>
        </div>

        {/* Colonnes de liens organisees par usage: plateforme, ressources, entreprise et legal. */}
        {footerColumns.map(([title, links]) => (
          <div key={title}>
            <h2 className={`text-sm font-black ${titleText}`}>{title}</h2>
            <ul className="mt-4 space-y-3">
              {links.map((link) => (
                <li key={link.label}>
                  {link.legalType ? (
                    <button
                      className={`focus-ring rounded-sm text-left ${isDark ? 'hover:text-white' : 'hover:text-[#073f8f]'}`}
                      onClick={() => onOpenLegal?.(link.legalType)}
                      type="button"
                    >
                      {link.label}
                    </button>
                  ) : (
                    <a className={`focus-ring rounded-sm ${isDark ? 'hover:text-white' : 'hover:text-[#073f8f]'}`} href="#about">
                      {link.label}
                    </a>
                  )}
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className={`mx-auto mt-8 max-w-7xl border-t pt-5 text-center text-xs ${legalBorder} ${mutedText}`}>
        © 2026 University Key by teuguia. Tous droits réservés. {labels.legal}
      </div>
    </footer>
  )
}
