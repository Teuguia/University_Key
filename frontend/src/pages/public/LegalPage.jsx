// Commentaire d'intention: affiche les contenus legaux publics.

/**
 * Affiche la base des pages legales: conditions, confidentialite et mentions.
 */
export function LegalPage() {
  return (
    <section className="min-h-screen bg-white px-4 py-10 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-3xl">
        <h1 className="text-3xl font-black text-slate-950">Pages legales</h1>
        <div className="mt-6 space-y-4 text-slate-700">
          <p>Politique de confidentialite adaptee aux utilisateurs au Cameroun.</p>
          <p>Conditions d'utilisation pour les etudiants, parents, conseillers et administrateurs.</p>
          <p>Mentions legales avec editeur, hebergement, contact et procedure de signalement.</p>
        </div>
      </div>
    </section>
  )
}
