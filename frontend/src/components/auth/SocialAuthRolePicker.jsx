// Commentaire d'intention: garde les boutons sociaux visibles sans authentification active.

import { BrandIcon } from '../common/BrandIcon'

/**
 * Les fournisseurs sociaux sont gardes en vitrine frontend uniquement. Aucun
 * appel OAuth n'est declenche tant que le backend n'est pas branche.
 */
export function SocialAuthRolePicker({ labels }) {
  return (
    <div className="space-y-4">
      <p className="text-xs font-bold text-slate-500">{labels.socialDisabledHint}</p>

      <div className="grid gap-4 sm:grid-cols-2">
        {['google', 'facebook'].map((provider) => (
          <button
            aria-disabled="true"
            className="inline-flex min-h-11 cursor-not-allowed items-center justify-center gap-3 rounded-md border border-slate-200 bg-slate-50 text-sm font-black text-slate-400"
            key={provider}
            tabIndex={-1}
            type="button"
          >
            <BrandIcon name={provider} />
            {provider === 'google' ? 'Google' : 'Facebook'}
          </button>
        ))}
      </div>
    </div>
  )
}
