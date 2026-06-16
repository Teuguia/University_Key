import { adminModules } from '../../data/mockData'

/**
 * Resume les controles critiques attendus dans l'espace administrateur.
 */
export function AdminPreview({ labels }) {
  return (
    <section className="bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8" id="admin">
      <div className="mx-auto max-w-7xl">
        <h2 className="text-2xl font-black">{labels.adminTitle}</h2>
        <p className="mt-3 max-w-2xl text-slate-300">{labels.adminText}</p>
        <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
          {adminModules.map((module) => (
            <button
              className="focus-ring rounded-md border border-slate-700 bg-slate-900 px-4 py-4 text-left font-bold hover:bg-slate-800"
              key={module}
              type="button"
            >
              {module}
            </button>
          ))}
        </div>
      </div>
    </section>
  )
}

