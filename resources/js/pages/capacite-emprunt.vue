<script setup>
import { useTheme } from 'vuetify'
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import { formatDuration, formatXOF } from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const vuetifyTheme = useTheme()

const DEBT_RATIO = 40 // Taux d'endettement maximum (%)

const form = ref({
  monthly_income: 500000,
  monthly_charges: 100000,
  annual_rate: 8,
  duration_months: 180,
})

// 👉 Calculs
const analysis = computed(() => {
  const { monthly_income: income, monthly_charges: charges, annual_rate: rate, duration_months: months } = form.value

  if (!income || income <= 0 || !months || months < 1)
    return null

  const i = rate / 12 / 100
  const available = Math.max(0, income - (charges || 0))
  const maxPayment = available * DEBT_RATIO / 100

  const capacity = i > 0
    ? maxPayment * (1 - (1 + i) ** -months) / i
    : maxPayment * months

  const currentRatio = (charges || 0) / income * 100

  return {
    maxPayment,
    capacity,
    totalInterests: maxPayment * months - capacity,
    currentRatio,
    remainingAfterPayment: available - maxPayment,
  }
})

// 👉 Capacité selon la durée (graphique)
const durations = [60, 120, 180, 240, 300, 360]

const chartSeries = computed(() => {
  if (!analysis.value)
    return []

  const i = form.value.annual_rate / 12 / 100
  const maxPayment = analysis.value.maxPayment

  return [{
    name: 'Capacité d\'emprunt',
    data: durations.map(n => Math.round(
      i > 0 ? maxPayment * (1 - (1 + i) ** -n) / i : maxPayment * n,
    )),
  }]
})

const chartOptions = computed(() => {
  const onSurfaceMuted = `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.6)`

  return {
    chart: { parentHeightOffset: 0, toolbar: { show: false } },
    plotOptions: {
      bar: {
        columnWidth: '50%',
        borderRadius: 6,
        distributed: true,
      },
    },
    colors: durations.map(n => (n === Number(form.value.duration_months) ? '#7367F0' : '#B5B1E8')),
    dataLabels: { enabled: false },
    legend: { show: false },
    grid: { borderColor: `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.12)` },
    xaxis: {
      categories: durations.map(n => `${n / 12} ans`),
      labels: { style: { colors: onSurfaceMuted } },
    },
    yaxis: {
      labels: {
        style: { colors: onSurfaceMuted },
        formatter: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v),
      },
    },
    tooltip: { y: { formatter: v => formatXOF(v) } },
  }
})

const gaugeColor = computed(() => {
  const ratio = analysis.value?.currentRatio ?? 0

  if (ratio < 25)
    return 'success'
  if (ratio < DEBT_RATIO)
    return 'warning'

  return 'error'
})
</script>

<template>
  <div class="front-page capacity-page">
    <FrontNavbar />

    <div class="front-container py-8">
      <div class="mb-6">
        <h1 class="front-display capacity-title mb-1">
          Capacité d'emprunt
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Estimez le montant que vous pouvez emprunter, avec un taux d'endettement plafonné à {{ DEBT_RATIO }} %.
        </p>
      </div>

      <VRow>
        <!-- 👉 Formulaire -->
        <VCol
          cols="12"
          md="4"
        >
          <VCard class="capacity-form-card">
            <div class="kente-band" />
            <VCardText class="pa-6">
              <VRow dense>
                <VCol cols="12">
                  <AppTextField
                    v-model.number="form.monthly_income"
                    label="Revenus mensuels nets"
                    type="number"
                    suffix="FCFA"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model.number="form.monthly_charges"
                    label="Charges et crédits en cours"
                    type="number"
                    suffix="FCFA"
                    hint="Loyers, pensions, mensualités d'autres crédits..."
                    persistent-hint
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
                    :hint="formatDuration(form.duration_months || 0)"
                    persistent-hint
                  />
                </VCol>
              </VRow>

              <VAlert
                v-if="analysis"
                :type="gaugeColor"
                variant="tonal"
                class="mt-5"
                density="compact"
              >
                Endettement actuel : {{ analysis.currentRatio.toFixed(1) }} %
                (plafond {{ DEBT_RATIO }} %)
              </VAlert>
            </VCardText>
          </VCard>
        </VCol>

        <!-- 👉 Résultats -->
        <VCol
          cols="12"
          md="8"
        >
          <template v-if="analysis">
            <VCard
              class="capacity-hero-card mb-6"
              variant="flat"
            >
              <VCardText class="pa-6 text-center">
                <p class="text-body-1 text-medium-emphasis mb-1">
                  Vous pouvez emprunter jusqu'à
                </p>
                <div class="front-mono capacity-hero-value mb-2">
                  {{ formatXOF(analysis.capacity) }}
                </div>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  sur {{ formatDuration(form.duration_months) }} à {{ form.annual_rate.toLocaleString('fr-FR') }} %,
                  avec une mensualité maximale de <strong>{{ formatXOF(analysis.maxPayment) }}</strong>
                </p>
                <VBtn
                  :to="{ path: '/simulateur', query: { amount: Math.round(analysis.capacity), rate: form.annual_rate, months: form.duration_months, method: 'annuity' } }"
                  append-icon="tabler-arrow-right"
                >
                  Simuler ce prêt en détail
                </VBtn>
              </VCardText>
            </VCard>

            <VRow class="mb-1">
              <VCol
                cols="12"
                sm="4"
              >
                <VCard
                  variant="flat"
                  class="capacity-summary-card h-100"
                >
                  <VCardText class="pa-4">
                    <div class="text-body-2 text-medium-emphasis">
                      Mensualité maximale
                    </div>
                    <div class="front-mono capacity-summary-value">
                      {{ formatXOF(analysis.maxPayment) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ DEBT_RATIO }} % de vos revenus disponibles
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol
                cols="12"
                sm="4"
              >
                <VCard
                  variant="flat"
                  class="capacity-summary-card h-100"
                >
                  <VCardText class="pa-4">
                    <div class="text-body-2 text-medium-emphasis">
                      Intérêts sur la durée
                    </div>
                    <div class="front-mono capacity-summary-value">
                      {{ formatXOF(analysis.totalInterests) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      Si vous empruntez le maximum
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol
                cols="12"
                sm="4"
              >
                <VCard
                  variant="flat"
                  class="capacity-summary-card h-100"
                >
                  <VCardText class="pa-4">
                    <div class="text-body-2 text-medium-emphasis">
                      Reste à vivre mensuel
                    </div>
                    <div class="front-mono capacity-summary-value">
                      {{ formatXOF(analysis.remainingAfterPayment) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      Après charges et mensualité max
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>

            <VCard>
              <VCardItem>
                <VCardTitle>Capacité selon la durée</VCardTitle>
                <VCardSubtitle>À mensualité maximale constante — votre durée actuelle est en surbrillance</VCardSubtitle>
              </VCardItem>
              <VCardText>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="chartOptions"
                  :series="chartSeries"
                />
              </VCardText>
            </VCard>

            <p class="text-caption text-medium-emphasis mt-4">
              Estimation indicative : chaque établissement applique ses propres critères
              (apport, stabilité des revenus, reste à vivre...).
            </p>
          </template>
        </VCol>
      </VRow>
    </div>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.capacity-page {
  min-block-size: 100vh;

  .capacity-title {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 700;
  }

  .capacity-form-card {
    inset-block-start: 84px;
    overflow: hidden;
    position: sticky;
  }

  .capacity-hero-card {
    background:
      radial-gradient(ellipse 70% 90% at 50% 0%, rgba(115, 103, 240, 12%), transparent),
      rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-primary), 0.25);
  }

  .capacity-hero-value {
    color: rgb(var(--v-theme-primary));
    font-size: clamp(2rem, 4.5vw, 3rem);
    font-weight: 600;
    line-height: 1.15;
  }

  .capacity-summary-card {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .capacity-summary-value {
    font-size: 1.15rem;
    font-weight: 600;
    line-height: 1.6;
  }
}
</style>
