/**
 * Shared expiration helpers used across client and admin pages.
 * Returns raw signed days (negative = already expired).
 */
export function useExpiration() {
  const daysLeft = (endDate) => {
    if (!endDate) return null
    return Math.ceil((new Date(endDate) - new Date()) / 86400000)
  }

  const isExpired = (endDate) => {
    const d = daysLeft(endDate)
    return d !== null && d <= 0
  }

  const expiryColor = (endDate) => {
    const d = daysLeft(endDate)
    if (d === null) return 'default'
    if (d <= 0)  return 'error'
    if (d <= 3)  return 'error'
    if (d <= 7)  return 'warning'
    return 'success'
  }

  const expiryLabel = (endDate) => {
    const d = daysLeft(endDate)
    if (d === null) return ''
    if (d <= 0) return 'Expiré'
    if (d === 1) return '1 jour restant'
    return `${d} jours restants`
  }

  const expiryChipLabel = (endDate) => {
    const d = daysLeft(endDate)
    if (d === null) return ''
    if (d <= 0) return 'Expiré'
    return `${d}j`
  }

  const progressValue = (startDate, endDate) => {
    if (!startDate || !endDate) return 0
    const total = new Date(endDate) - new Date(startDate)
    const elapsed = new Date() - new Date(startDate)
    return Math.min(100, Math.max(0, (elapsed / total) * 100))
  }

  return { daysLeft, isExpired, expiryColor, expiryLabel, expiryChipLabel, progressValue }
}
