// Commentaire d'intention: affiche les textes legaux dans une fenetre modale.

import { useEffect, useMemo, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

// Transforme le markdown simple stocke en base en elements React lisibles.
function renderLegalLine(line, index) {
  const trimmedLine = line.trim()

  if (!trimmedLine) {
    return <div className="h-3" key={index} />
  }

  if (trimmedLine.startsWith('# ')) {
    return (
      <h1 className="mt-2 text-2xl font-black leading-tight text-[#073f8f] sm:text-3xl" key={index}>
        {trimmedLine.replace('# ', '')}
      </h1>
    )
  }

  if (trimmedLine.startsWith('## ')) {
    return (
      <h2 className="mt-7 text-xl font-black leading-tight text-[#073f8f]" key={index}>
        {trimmedLine.replace('## ', '')}
      </h2>
    )
  }

  if (trimmedLine.startsWith('### ')) {
    return (
      <h3 className="mt-5 text-base font-black leading-tight text-[#073f8f]" key={index}>
        {trimmedLine.replace('### ', '')}
      </h3>
    )
  }

  if (trimmedLine.startsWith('* ')) {
    return (
      <p className="ml-4 mt-2 text-sm font-bold leading-7 text-[#073f8f]" key={index}>
        * {trimmedLine.replace('* ', '')}
      </p>
    )
  }

  return (
    <p className="mt-3 text-sm leading-7 text-slate-950" key={index}>
      {trimmedLine}
    </p>
  )
}

/**
 * Fenetre modale qui affiche les textes legaux stockes dans la table regles.
 */
export function LegalModal({ labels, type, onClose }) {
  const [regles, setRegles] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!type) {
      return undefined
    }

    let isMounted = true

    // Les textes legaux sont administres cote backend dans la table regles.
    apiRequest('/regles')
      .then((payload) => {
        if (isMounted) {
          setRegles(payload.data)
          setError('')
        }
      })
      .catch(() => {
        if (isMounted) {
          setError(labels.error)
        }
      })

    return () => {
      isMounted = false
    }
  }, [labels.error, type])

  // La cle type choisit conditions ou politique dans la reponse API.
  const content = regles?.[type] ?? ''
  const lines = useMemo(() => content.split('\n'), [content])

  if (!type) {
    return null
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 px-4 py-8" onClick={onClose} role="presentation">
      <section
        aria-labelledby="legal-modal-title"
        aria-modal="true"
        className="max-h-[86vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-2xl shadow-slate-950/30"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
      >
        <header className="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-7">
          <h2 className="text-lg font-black text-[#061d49]" id="legal-modal-title">
            {labels.titles[type]}
          </h2>
          <button
            aria-label={labels.close}
            className="focus-ring grid h-9 w-9 place-items-center rounded-md border border-slate-200 text-xl font-black text-[#073f8f] hover:bg-blue-50"
            onClick={onClose}
            type="button"
          >
            x
          </button>
        </header>

        <div className="max-h-[72vh] overflow-y-auto px-5 py-6 sm:px-8">
          {!regles && !error && <p className="text-sm font-bold text-slate-950">{labels.loading}</p>}
          {error && <p className="text-sm font-bold text-red-600">{error}</p>}
          {regles && <div>{lines.map(renderLegalLine)}</div>}
        </div>
      </section>
    </div>
  )
}
