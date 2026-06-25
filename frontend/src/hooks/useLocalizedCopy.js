import { useMemo } from 'react'
// Commentaire d'intention: fournit le dictionnaire localise selon la langue active.

import { copy } from '../i18n/copy'

/**
 * Retourne les textes de l'interface selon la langue active.
 */
export function useLocalizedCopy(language) {
  return useMemo(() => copy[language], [language])
}
