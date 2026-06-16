/**
 * Affiche l'espace conseiller avec statut de validation, disponibilites et conversations.
 */
export function CounselorDashboardPage() {
  return (
    <section className="min-h-screen bg-white px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-3xl font-black text-slate-950">Espace conseiller</h1>
        <div className="mt-6 rounded-lg border border-orange-200 bg-orange-50 p-5">
          <h2 className="text-xl font-black text-orange-900">Validation manuelle requise</h2>
          <p className="mt-2 text-orange-900">
            Les justificatifs seront verifies par un administrateur avant publication du profil.
          </p>
        </div>
      </div>
    </section>
  )
}

