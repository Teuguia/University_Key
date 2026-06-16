/**
 * Affiche le tableau de bord etudiant avec profil, test et recommandations.
 */
export function StudentDashboardPage() {
  return (
    <section className="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-3xl font-black text-slate-950">Tableau de bord etudiant</h1>
        <div className="mt-6 grid gap-4 md:grid-cols-3">
          {['Profil', 'Test orientation', 'Recommandations'].map((item) => (
            <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" key={item}>
              <h2 className="text-xl font-black text-slate-950">{item}</h2>
              <p className="mt-2 text-sm text-slate-600">Module a connecter a l'API Laravel.</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

