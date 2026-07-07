/**
 * Utilitaires de calcul de prêt côté client.
 *
 * ⚠️ Le backend (App\Services\LoanCalculator) reste la source de vérité :
 * ces fonctions ne servent qu'aux aperçus instantanés (hero de la page
 * d'accueil) sans consommer le quota de simulations.
 */

export const LOAN_METHODS = [
  {
    value: 'annuity',
    title: 'Annuités constantes',
    short: 'Mensualité fixe',
    hint: 'La formule classique des banques : vous payez la même mensualité du début à la fin.',
  },
  {
    value: 'constant_capital',
    title: 'Amortissement constant',
    short: 'Capital fixe',
    hint: 'Vous remboursez la même part de capital chaque mois : les mensualités diminuent avec le temps.',
  },
  {
    value: 'in_fine',
    title: 'In fine',
    short: 'Capital à la fin',
    hint: 'Vous ne payez que les intérêts chaque mois, le capital est remboursé en une fois à la fin.',
  },
]

export const methodLabel = value =>
  LOAN_METHODS.find(m => m.value === value)?.title ?? value

export const formatXOF = (value, maximumFractionDigits = 0) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits,
  }).format(value ?? 0)

export const formatNumber = value =>
  new Intl.NumberFormat('fr-FR').format(value ?? 0)

/** Durée en mois → « 15 ans », « 15 ans et 6 mois » ou « 9 mois ». */
export const formatDuration = months => {
  const years = Math.floor(months / 12)
  const rest = months % 12
  if (!years)
    return `${rest} mois`
  if (!rest)
    return `${years} an${years > 1 ? 's' : ''}`

  return `${years} an${years > 1 ? 's' : ''} et ${rest} mois`
}

/**
 * Première mensualité (hors assurance) selon la méthode d'amortissement.
 */
export const firstMonthlyPayment = (amount, annualRate, months, method = 'annuity') => {
  const i = annualRate / 12 / 100

  switch (method) {
  case 'constant_capital':
    return amount / months + amount * i
  case 'in_fine':
    return i > 0 ? amount * i : amount / months
  default: // annuity
    return i > 0 ? amount * i / (1 - (1 + i) ** -months) : amount / months
  }
}

/**
 * Total des intérêts sur toute la durée (hors assurance), selon la méthode.
 */
export const totalInterest = (amount, annualRate, months, method = 'annuity') => {
  const i = annualRate / 12 / 100

  switch (method) {
  case 'constant_capital':
    return amount * i * (months + 1) / 2
  case 'in_fine':
    return amount * i * months
  default: // annuity
    return firstMonthlyPayment(amount, annualRate, months, 'annuity') * months - amount
  }
}
