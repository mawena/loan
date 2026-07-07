<script setup>
import { useTheme } from 'vuetify'
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import { formatDuration, formatXOF, generateSchedule } from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const vuetifyTheme = useTheme()

// 👉 Prêt initial (annuités constantes, le cas des banques)
const form = ref({
  amount: 15000000,
  annual_rate: 8,
  duration_months: 180,
  prepayment_month: 36,
  prepayment_amount: 3000000,
  strategy: 'reduce_duration',
})

const strategies = [
  {
    value: 'reduce_duration',
    title: 'Réduire la durée',
    hint: 'Même mensualité, prêt terminé plus tôt — le plus économique.',
  },
  {
    value: 'reduce_payment',
    title: 'Réduire la mensualité',
    hint: 'Même durée, mensualité allégée — plus de souplesse chaque mois.',
  },
]

// 👉 Calculs
const analysis = computed(() => {
  const { amount, annual_rate: rate, duration_months: months, prepayment_month: k, prepayment_amount: extra, strategy } = form.value

  if (!amount || !months || k < 1 || k >= months || !extra || extra <= 0)
    return null

  const i = rate / 12 / 100
  const original = generateSchedule(amount, rate, months, 'annuity')
  const originalInterests = original.reduce((sum, row) => sum + row.interest, 0)
  const payment = original[0].payment

  // Solde après l'échéance k, puis remboursement anticipé
  const balanceAtK = original[k - 1].balance
  const newBalance = Math.max(0, balanceAtK - extra)

  if (newBalance === 0) {
    return {
      fullPayoff: true,
      payment,
      originalInterests,
      newInterests: original.slice(0, k).reduce((s, r) => s + r.interest, 0),
      interestsSaved: original.slice(k).reduce((s, r) => s + r.interest, 0),
      monthsSaved: months - k,
      newDuration: k,
      newPayment: 0,
      balances: { original, after: original.slice(0, k) },
      prepaymentMonth: k,
    }
  }

  let after
  let newDuration = months
  let newPayment = payment

  if (strategy === 'reduce_duration') {
    // Mensualité inchangée : combien de mois pour solder newBalance ?
    const n = i > 0
      ? Math.ceil(-Math.log(1 - newBalance * i / payment) / Math.log(1 + i))
      : Math.ceil(newBalance / payment)

    after = [...original.slice(0, k), ...generateSchedule(newBalance, rate, n, 'annuity')]
    newDuration = k + n
  }
  else {
    // Durée inchangée : nouvelle mensualité sur les mois restants
    const n = months - k

    after = [...original.slice(0, k), ...generateSchedule(newBalance, rate, n, 'annuity')]
    newPayment = after[k].payment
  }

  const newInterests = after.reduce((sum, row) => sum + row.interest, 0)

  return {
    fullPayoff: false,
    payment,
    originalInterests,
    newInterests,
    interestsSaved: originalInterests - newInterests,
    monthsSaved: months - newDuration,
    newDuration,
    newPayment,
    balances: { original, after },
    prepaymentMonth: k,
  }
})

// 👉 Graphique : solde restant avant / après
const chartSeries = computed(() => {
  if (!analysis.value)
    return []

  const { original, after } = analysis.value.balances
  const length = original.length

  return [
    { name: 'Sans remboursement anticipé', data: original.map(r => Math.round(r.balance)) },
    {
      name: 'Avec remboursement anticipé',
      data: Array.from({ length }, (_, idx) => {
        const row = after[idx]

        return row ? Math.round(row.balance) : null
      }),
    },
  ]
})

const chartOptions = computed(() => {
  const onSurfaceMuted = `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.6)`
  const months = form.value.duration_months

  return {
    chart: { parentHeightOffset: 0, toolbar: { show: false }, zoom: { enabled: false } },
    colors: ['#B5B1E8', '#7367F0'],
    stroke: { curve: 'straight', width: [2, 3], dashArray: [6, 0] },
    dataLabels: { enabled: false },
    legend: { labels: { colors: onSurfaceMuted } },
    grid: { borderColor: `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.12)` },
    xaxis: {
      type: 'numeric',
      tickAmount: 8,
      max: months,
      labels: {
        style: { colors: onSurfaceMuted },
        formatter: v => `${Math.round(v / 12)} an${v >= 24 ? 's' : ''}`,
      },
    },
    yaxis: {
      labels: {
        style: { colors: onSurfaceMuted },
        formatter: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v),
      },
    },
    annotations: {
      xaxis: [{
        x: form.value.prepayment_month,
        borderColor: '#E8A33D',
        label: {
          text: 'Remboursement anticipé',
          style: { color: '#fff', background: '#E8A33D' },
        },
      }],
    },
    tooltip: { y: { formatter: v => (v === null ? '—' : formatXOF(v)) } },
  }
})

const resultCards = computed(() => {
  if (!analysis.value)
    return []

  const a = analysis.value

  return [
    {
      icon: 'tabler-pig-money',
      label: 'Intérêts économisés',
      value: formatXOF(a.interestsSaved),
      caption: `${formatXOF(a.newInterests)} au lieu de ${formatXOF(a.originalInterests)}`,
      color: 'success',
    },
    a.fullPayoff || form.value.strategy === 'reduce_duration'
      ? {
        icon: 'tabler-calendar-minus',
        label: 'Durée réduite',
        value: `− ${formatDuration(a.monthsSaved)}`,
        caption: `Prêt soldé en ${formatDuration(a.newDuration)}`,
        color: 'primary',
      }
      : {
        icon: 'tabler-trending-down',
        label: 'Nouvelle mensualité',
        value: formatXOF(a.newPayment),
        caption: `Au lieu de ${formatXOF(a.payment)} (− ${formatXOF(a.payment - a.newPayment)})`,
        color: 'primary',
      },
    {
      icon: 'tabler-cash',
      label: 'Mensualité actuelle',
      value: formatXOF(a.payment),
      caption: 'Annuités constantes',
      color: 'secondary',
    },
  ]
})
</script>

<template>
  <div class="front-page prepay-page">
    <FrontNavbar />

    <div class="front-container py-8">
      <div class="mb-6">
        <h1 class="front-display prepay-title mb-1">
          Remboursement anticipé
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Un capital disponible ? Mesurez ce qu'un remboursement anticipé vous ferait économiser.
        </p>
      </div>

      <VRow>
        <!-- 👉 Formulaire -->
        <VCol
          cols="12"
          md="4"
        >
          <VCard class="prepay-form-card">
            <div class="kente-band" />
            <VCardText class="pa-6">
              <p class="text-body-2 font-weight-medium mb-3">
                Votre prêt en cours
              </p>
              <VRow dense>
                <VCol cols="12">
                  <AppTextField
                    v-model.number="form.amount"
                    label="Montant emprunté"
                    type="number"
                    suffix="FCFA"
                  />
                </VCol>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="form.annual_rate"
                    label="Taux annuel"
                    type="number"
                    step="0.01"
                    suffix="%"
                  />
                </VCol>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="form.duration_months"
                    label="Durée"
                    type="number"
                    suffix="mois"
                  />
                </VCol>
              </VRow>

              <VDivider class="my-5" />

              <p class="text-body-2 font-weight-medium mb-3">
                Votre remboursement anticipé
              </p>
              <VRow dense>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="form.prepayment_month"
                    label="Au mois n°"
                    type="number"
                    :hint="`Soit après ${formatDuration(form.prepayment_month || 0)}`"
                    persistent-hint
                  />
                </VCol>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="form.prepayment_amount"
                    label="Montant versé"
                    type="number"
                    suffix="FCFA"
                  />
                </VCol>
                <VCol cols="12">
                  <VLabel class="text-body-2 font-weight-medium mb-2 mt-2">
                    Que préférez-vous ?
                  </VLabel>
                  <VRadioGroup v-model="form.strategy">
                    <VRadio
                      v-for="s in strategies"
                      :key="s.value"
                      :value="s.value"
                    >
                      <template #label>
                        <div>
                          <span class="d-block font-weight-medium">{{ s.title }}</span>
                          <span class="d-block text-caption text-medium-emphasis">{{ s.hint }}</span>
                        </div>
                      </template>
                    </VRadio>
                  </VRadioGroup>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <!-- 👉 Résultats -->
        <VCol
          cols="12"
          md="8"
        >
          <template v-if="analysis">
            <VAlert
              v-if="analysis.fullPayoff"
              type="success"
              variant="tonal"
              class="mb-4"
              icon="tabler-confetti"
            >
              Ce versement solde entièrement votre prêt au mois {{ analysis.prepaymentMonth }} !
            </VAlert>

            <VRow class="mb-1">
              <VCol
                v-for="card in resultCards"
                :key="card.label"
                cols="12"
                sm="4"
              >
                <VCard
                  variant="flat"
                  class="prepay-summary-card h-100"
                >
                  <VCardText class="pa-4">
                    <VAvatar
                      :color="card.color"
                      variant="tonal"
                      rounded
                      size="38"
                      class="mb-3"
                    >
                      <VIcon
                        :icon="card.icon"
                        size="22"
                      />
                    </VAvatar>
                    <div class="text-body-2 text-medium-emphasis">
                      {{ card.label }}
                    </div>
                    <div class="front-mono prepay-summary-value">
                      {{ card.value }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ card.caption }}
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>

            <VCard>
              <VCardItem>
                <VCardTitle>Évolution du solde restant dû</VCardTitle>
                <VCardSubtitle>Avant / après votre remboursement anticipé</VCardSubtitle>
              </VCardItem>
              <VCardText>
                <VueApexCharts
                  type="line"
                  height="320"
                  :options="chartOptions"
                  :series="chartSeries"
                />
              </VCardText>
            </VCard>

            <p class="text-caption text-medium-emphasis mt-4">
              Calcul indicatif en annuités constantes, hors indemnités de remboursement anticipé
              éventuelles prévues par votre contrat.
            </p>
          </template>

          <VCard
            v-else
            variant="flat"
            class="prepay-empty d-flex align-center justify-center"
          >
            <VCardText class="text-center py-16">
              <VAvatar
                color="primary"
                variant="tonal"
                size="72"
                class="mb-4"
              >
                <VIcon
                  icon="tabler-coins"
                  size="40"
                />
              </VAvatar>
              <h3 class="text-h5 front-display mb-2">
                Renseignez votre prêt et votre versement
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Le mois du versement doit être avant la fin du prêt, et le montant supérieur à zéro.
              </p>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.prepay-page {
  min-block-size: 100vh;

  .prepay-title {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 700;
  }

  .prepay-form-card {
    inset-block-start: 84px;
    overflow: hidden;
    position: sticky;
  }

  .prepay-summary-card {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .prepay-summary-value {
    font-size: 1.15rem;
    font-weight: 600;
    line-height: 1.5;
  }

  .prepay-empty {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 2px dashed rgba(var(--v-border-color), calc(var(--v-border-opacity) * 2));
    min-block-size: 420px;
  }
}
</style>
