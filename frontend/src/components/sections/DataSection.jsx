/**
 * Affiche une collection de cartes simples pour les filieres, ecoles ou conseillers.
 */
export function DataSection({ id, labels, title, items, renderItem }) {
  return (
    <section className="border-b border-slate-200 bg-white px-4 py-10 sm:px-6 lg:px-8" id={id}>
      <div className="mx-auto max-w-7xl">
        <div className="mb-5 flex items-end justify-between gap-4">
          <h2 className="text-2xl font-black text-slate-950">{title}</h2>
          <a
            className="focus-ring rounded-md px-3 py-2 text-sm font-black text-emerald-700 hover:bg-emerald-50"
            href={`#${id}`}
          >
            {labels.viewAll}
          </a>
        </div>
        <div className="grid gap-4 md:grid-cols-3">{items.map(renderItem)}</div>
      </div>
    </section>
  )
}

