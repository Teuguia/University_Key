/**
 * Affiche le tableau de bord administrateur avec les modules de controle principaux.
 */
export function AdminDashboardPage() {
  const cards = ['Utilisateurs', 'Conseillers en attente', 'Ecoles', 'Filieres', 'Messages', 'Logs']

  return (
    <section className="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-3xl font-black">Administration University Key</h1>
        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {cards.map((card) => (
            <article className="rounded-lg border border-slate-700 bg-slate-900 p-5" key={card}>
              <h2 className="text-xl font-black">{card}</h2>
              <p className="mt-2 text-sm text-slate-300">Acces a securiser par role administrateur.</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

