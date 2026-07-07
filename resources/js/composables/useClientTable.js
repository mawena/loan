// Compare deux valeurs de colonnes pour le tri (nombres, booléens, texte FR insensible à la casse/accents)
const compareValues = (a, b) => {
  if (a == null && b == null)
    return 0
  if (a == null)
    return -1
  if (b == null)
    return 1
  if (typeof a === 'number' && typeof b === 'number')
    return a - b
  if (typeof a === 'boolean' && typeof b === 'boolean')
    return Number(a) - Number(b)

  return String(a).localeCompare(String(b), 'fr', { sensitivity: 'base', numeric: true })
}

// Vérifie si un item correspond à une recherche texte sur une liste de champs
export const matchesSearch = (item, fields, query) => {
  const normalizedQuery = query.trim().toLowerCase()
  if (!normalizedQuery)
    return true

  return fields.some(field => String(item[field] ?? '').toLowerCase().includes(normalizedQuery))
}

// Gère le tri et la pagination côté frontend d'une liste déjà filtrée, pour alimenter un VDataTableServer
// sans appel réseau supplémentaire (la recherche/le filtrage restent à la charge de l'appelant).
export function useClientTable(filteredItems, defaultItemsPerPage = 10) {
  const page = ref(1)
  const itemsPerPage = ref(defaultItemsPerPage)
  const sortBy = ref([])

  const updateOptions = options => {
    sortBy.value = options.sortBy
  }

  const sortedItems = computed(() => {
    if (!sortBy.value.length)
      return filteredItems.value

    return [...filteredItems.value].sort((a, b) => {
      for (const { key, order } of sortBy.value) {
        const result = compareValues(a[key], b[key])
        if (result !== 0)
          return order === 'desc' ? -result : result
      }

      return 0
    })
  })

  const totalItems = computed(() => sortedItems.value.length)

  const paginatedItems = computed(() => {
    if (itemsPerPage.value === -1)
      return sortedItems.value

    const start = (page.value - 1) * itemsPerPage.value

    return sortedItems.value.slice(start, start + itemsPerPage.value)
  })

  // Revenir à la première page dès que le jeu de données filtré change (recherche, filtres...)
  watch(filteredItems, () => {
    page.value = 1
  })

  return {
    page,
    itemsPerPage,
    sortBy,
    updateOptions,
    paginatedItems,
    totalItems,
  }
}
