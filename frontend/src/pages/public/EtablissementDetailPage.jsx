// Commentaire d'intention: affiche la fiche publique detaillee d'un etablissement.

import { useEffect, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

function formatFees(value) {
  if (!value) {
    return 'Non precise'
  }

  return `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`
}

function InfoRow({ label, value }) {
  return (
    <div className="border-b border-slate-100 py-3 last:border-b-0">
      <p className="text-xs font-black uppercase text-slate-500">{label}</p>
      <p className="mt-1 text-sm font-bold text-[#061d49]">{value || 'Non precise'}</p>
    </div>
  )
}

/**
 * Page publique detail etablissement: photos, filieres et informations pratiques.
 */
export function EtablissementDetailPage({ etablissementId }) {
  const [status, setStatus] = useState({ state: 'loading', message: '' })
  const [school, setSchool] = useState(null)

  useEffect(() => {
    let isMounted = true

    apiRequest(`/etablissements/${etablissementId}`)
      .then((payload) => {
        if (isMounted) {
          setSchool(payload.data)
          setStatus({ state: 'ready', message: '' })
        }
      })
      .catch((error) => {
        if (isMounted) {
          setSchool(null)
          setStatus({ state: 'error', message: error.message })
        }
      })

    return () => {
      isMounted = false
    }
  }, [etablissementId])

  if (status.state === 'loading') {
    return (
      <section className="min-h-screen bg-slate-50 px-4 py-12">
        <p className="mx-auto max-w-5xl rounded-lg border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-600">Chargement de l'etablissement...</p>
      </section>
    )
  }

  if (status.state === 'error' || !school) {
    return (
      <section className="min-h-screen bg-slate-50 px-4 py-12">
        <div className="mx-auto max-w-5xl rounded-lg border border-red-100 bg-white px-5 py-4">
          <p className="text-sm font-bold text-red-700">{status.message || 'Etablissement introuvable.'}</p>
          <a className="mt-4 inline-flex min-h-10 items-center rounded-md bg-[#073f8f] px-4 text-sm font-black text-white" href="#admin">Retour</a>
        </div>
      </section>
    )
  }

  const photos = school.photos?.length ? school.photos : [school.logo_url].filter(Boolean)

  return (
    <section className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <a className="text-sm font-black text-[#074fb2]" href="#admin">Retour a la recherche</a>

        <div className="mt-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
          <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
            <div>
              <p className="text-sm font-black uppercase text-[#074fb2]">{school.type_label}</p>
              <h1 className="mt-2 text-3xl font-black text-[#061d49]">{school.name}</h1>
              <p className="mt-2 text-sm font-bold text-slate-500">{school.city} {school.region ? `· ${school.region}` : ''}</p>
            </div>
            {school.logo_url && (
              <img alt="" className="h-20 w-20 rounded-lg border border-slate-200 object-contain p-2" src={school.logo_url} />
            )}
          </div>

          {school.description && (
            <p className="mt-5 max-w-4xl text-sm font-medium leading-7 text-slate-600">{school.description}</p>
          )}
        </div>

        <div className="mt-6 grid gap-6 lg:grid-cols-[0.9fr_1.2fr_0.9fr]">
          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
            <h2 className="text-lg font-black text-[#061d49]">Galerie photos</h2>
            <div className="mt-4 grid gap-3">
              {photos.length === 0 ? (
                <div className="grid aspect-video place-items-center rounded-lg bg-slate-100 text-sm font-bold text-slate-500">Aucune photo disponible</div>
              ) : (
                photos.slice(0, 5).map((photo, index) => (
                  <img alt="" className="aspect-video w-full rounded-lg border border-slate-100 object-cover" key={`${photo}-${index}`} src={photo} />
                ))
              )}
            </div>
          </section>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
            <h2 className="text-lg font-black text-[#061d49]">Liste des filieres</h2>
            <div className="mt-4 space-y-3">
              {school.filieres?.length ? school.filieres.map((filiere) => (
                <article className="rounded-lg border border-slate-100 bg-slate-50 p-4" key={filiere.id}>
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h3 className="text-sm font-black text-[#061d49]">{filiere.name}</h3>
                      <p className="mt-1 text-xs font-bold text-slate-500">{filiere.domain} · {filiere.level}</p>
                    </div>
                    <span className="rounded-md bg-blue-50 px-2 py-1 text-xs font-black text-[#074fb2]">{filiere.duration_years || '?'} ans</span>
                  </div>
                  <p className="mt-3 text-xs font-bold text-slate-600">{filiere.degree}</p>
                  <p className="mt-2 text-xs font-bold text-emerald-700">Frais: {formatFees(filiere.specific_fees)}</p>
                </article>
              )) : (
                <p className="rounded-md bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">Aucune filiere rattachee pour le moment.</p>
              )}
            </div>
          </section>

          <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
            <h2 className="text-lg font-black text-[#061d49]">Infos pratiques</h2>
            <div className="mt-4">
              <InfoRow label="Adresse" value={school.address} />
              <InfoRow label="Telephone" value={school.phone} />
              <InfoRow label="Email" value={school.email} />
              <InfoRow label="Site web" value={school.website} />
              <InfoRow label="Frais minimum" value={formatFees(school.fees?.min)} />
              <InfoRow label="Frais maximum" value={formatFees(school.fees?.max)} />
              <InfoRow label="Admission" value={school.admission} />
              <InfoRow label="Concours" value={school.has_competition ? (school.competition_details || 'Oui') : 'Non'} />
            </div>
          </section>
        </div>
      </div>
    </section>
  )
}
