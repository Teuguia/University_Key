// Commentaire d'intention: prepare le choix du role avant une authentification sociale.

import { BrandIcon } from '../common/BrandIcon'

const roleOptions = [
  { value: 'etudiant', labelKey: 'student' },
  { value: 'conseiller', labelKey: 'counselor' },
]

/**
 * Affiche le role voulu avant Google/Facebook afin que le backend sache quel
 * profil creer au premier passage OAuth.
 */
export function SocialAuthRolePicker({ labels, role, onRoleChange, onProviderSelect }) {
  return (
    <div className="space-y-4">
      <fieldset>
        <legend className="text-sm font-black text-[#06255a]">{labels.socialRoleLegend ?? labels.roleLegend}</legend>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          {roleOptions.map((option) => (
            <label
              className={`flex min-h-11 cursor-pointer items-center justify-center rounded-md border px-4 text-sm font-black ${
                role === option.value
                  ? 'border-[#073f8f] bg-blue-50 text-[#073f8f]'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
              }`}
              key={option.value}
            >
              <input checked={role === option.value} className="sr-only" name="social_role" onChange={() => onRoleChange(option.value)} type="radio" value={option.value} />
              {labels[option.labelKey]}
            </label>
          ))}
        </div>
        <p className="mt-2 text-xs font-bold text-slate-500">{labels.socialRoleHint}</p>
      </fieldset>

      <div className="grid gap-4 sm:grid-cols-2">
        {['google', 'facebook'].map((provider) => (
          <button
            className="focus-ring inline-flex min-h-11 items-center justify-center gap-3 rounded-md border border-slate-200 bg-white text-sm font-black text-slate-700 hover:bg-slate-50"
            key={provider}
            onClick={() => onProviderSelect(provider, role)}
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
