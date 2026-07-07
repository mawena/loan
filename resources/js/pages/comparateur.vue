<script setup>
import { useTheme } from 'vuetify'
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import {
  firstMonthlyPayment,
  formatDuration,
  formatXOF,
  LOAN_METHODS,
  methodLabel,
  totalInterest,
} from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const vuetifyTheme = useTheme()

const SCENARIO_COLORS = ['#7367F0', '#E8A33D', '#17897B']

const defaultScenario = (label, rate) => ({
  label,
  amount: 10000000,
  annual_rate: rate,
  duration_months: 120,
  method: 'annuity',
})

const scenarios = ref([
  defaultScenario('Offre A', 7),
  defaultScenario('Offre B', 8.5),
])

const addScenario = () => {
  if (scenarios.value.length < 3)
    scenarios.value.push(defaultScenario(`Offre ${'ABC'[scenarios.value.length]}`, 9))
}

const removeScenario = index => {
  if (scenarios.value.length > 2)
    scenarios.value.splice(index, 1)
}

// 👉 Résultats calculés en direct
const results = computed(() => scenarios.value.map((s, index) => {
  const payment = firstMonthlyPayment(s.amount, s.annual_rate, s.duration_months, s.method)
  const interests = totalInterest(s.amount, s.annual_rate, s.duration_months, s.method)

  return {
    ...s,
    color: SCENARIO_COLORS[index],
    payment,
    interests,
    totalCost: s.amount + interests,
  }
}))

const cheapestIndex = computed(() => {
  let best = 0

  results.value.forEach((r, i) => {
    if (r.interests < results.value[best].interests)
      best = i
  })

  return best
})

const savings = computed(() => {
  const sorted = [...results.value].sort((a, b) => a.interests - b.interests)

  return sorted.length > 1 ? sorted[1].interests - sorted[0].interests : 0
})

// 👉 Graphique comparatif
const chartSeries = computed(() => [
  { name: 'Mensualité (première)', data: results.value.map(r => Math.round(r.payment)) },
  { name: 'Total des intérêts', data: results.value.map(r => Math.round(r.interests)) },
])

const chartOptions = computed(() => {
  const onSurfaceMuted = `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.6)`

  return {
    chart: { parentHeightOffset: 0, toolbar: { show: false } },
    colors: ['#7367F0', '#E8A33D'],
    plotOptions: { bar: { columnWidth: '45%', borderRadius: 6 } },
    dataLabels: { enabled: false },
    legend: { labels: { colors: onSurfaceMuted } },
    grid: { borderColor: `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.12)` },
    xaxis: {
      categories: results.value.map(r => r.label),
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

const comparisonRows = computed(() => [
  { label: 'Montant emprunté', values: results.value.map(r => formatXOF(r.amount)) },
  { label: 'Taux annuel', values: results.value.map(r => `${r.annual_rate.toLocaleString('fr-FR')} %`) },
  { label: 'Durée', values: results.value.map(r => formatDuration(r.duration_months)) },
  { label: 'Méthode', values: results.value.map(r => methodLabel(r.method)) },
  { label: 'Mensualité (première)', values: results.value.map(r => formatXOF(r.payment)), strong: true },
  { label: 'Total des intérêts', values: results.value.map(r => formatXOF(r.interests)), strong: true },
  { label: 'Coût total (capital + intérêts)', values: results.value.map(r => formatXOF(r.totalCost)), strong: true },
])
</script>

<template>
  <div class="front-page comparator-page">
    <FrontNavbar />

    <div class="front-container py-8">
      <div class="mb-6">
        <h1 class="front-display comparator-title mb-1">
          Comparateur de prêts
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Comparez jusqu'à trois offres côte à côte et repérez la moins chère en un coup d'œil.
        </p>
      </div>

      <!-- 👉 Scénarios -->
      <VRow>
        <VCol
          v-for="(scenario, index) in scenarios"
          :key="index"
          cols="12"
          md="4"
        >
          <VCard
            class="comparator-card h-100"
            :style="`border-block-start: 4px solid ${SCENARIO_COLORS[index]}`"
          >
            <VCardText class="pa-5">
              <div class="d-flex justify-space-between align-center mb-4">
                <AppTextField
                  v-model="scenario.label"
                  density="compact"
                  style="max-inline-size: 160px;"
                />
                <IconBtn
                  v-if="scenarios.length > 2"
                  size="small"
                  @click="removeScenario(index)"
                >
                  <VIcon
                    icon="tabler-x"
                    size="18"
                  />
                </IconBtn>
              </div>

              <VRow dense>
                <VCol cols="12">
                  <AppTextField
                    v-model.number="scenario.amount"
                    label="Montant"
                    type="number"
                    suffix="FCFA"
                  />
                </VCol>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="scenario.annual_rate"
                    label="Taux annuel"
                    type="number"
                    step="0.01"
                    suffix="%"
                  />
                </VCol>
                <VCol cols="6">
                  <AppTextField
                    v-model.number="scenario.duration_months"
                    label="Durée"
                    type="number"
                    suffix="mois"
                  />
                </VCol>
                <VCol cols="12">
                  <AppSelect
                    v-model="scenario.method"
                    label="Méthode"
                    :items="LOAN_METHODS.map(m => ({ title: m.title, value: m.value }))"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <VCol
          v-if="scenarios.length < 3"
          cols="12"
          md="4"
        >
          <VCard
            variant="flat"
            class="comparator-add h-100 d-flex align-center justify-center"
            @click="addScenario"
          >
            <VCardText class="text-center">
              <VAvatar
                color="primary"
                variant="tonal"
                size="52"
                class="mb-3"
              >
                <VIcon
                  icon="tabler-plus"
                  size="30"
                />
              </VAvatar>
              <p class="text-body-1 font-weight-medium mb-0">
                Ajouter une 3ᵉ offre
              </p>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- 👉 Verdict -->
      <VAlert
        type="success"
        variant="tonal"
        class="mt-6"
        icon="tabler-trophy"
      >
        <strong>{{ results[cheapestIndex].label }}</strong> est l'offre la moins chère :
        {{ formatXOF(results[cheapestIndex].interests) }} d'intérêts au total<template v-if="savings > 0">
          ,
          soit <strong>{{ formatXOF(savings) }} d'économies</strong> par rapport à l'offre suivante
        </template>.
      </VAlert>

      <!-- 👉 Graphique + tableau -->
      <VRow class="mt-2">
        <VCol
          cols="12"
          lg="5"
        >
          <VCard class="h-100">
            <VCardItem>
              <VCardTitle>Comparaison visuelle</VCardTitle>
            </VCardItem>
            <VCardText>
              <VueApexCharts
                type="bar"
                height="320"
                :options="chartOptions"
                :series="chartSeries"
              />
            </VCardText>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          lg="7"
        >
          <VCard class="h-100">
            <VCardItem>
              <VCardTitle>Détail de la comparaison</VCardTitle>
            </VCardItem>
            <VTable class="comparator-table">
              <thead>
                <tr>
                  <th />
                  <th
                    v-for="(r, i) in results"
                    :key="i"
                    class="text-end"
                  >
                    <VChip
                      size="small"
                      :color="i === cheapestIndex ? 'success' : undefined"
                      :variant="i === cheapestIndex ? 'tonal' : 'text'"
                    >
                      {{ r.label }}
                      <VIcon
                        v-if="i === cheapestIndex"
                        icon="tabler-trophy"
                        size="14"
                        class="ms-1"
                      />
                    </VChip>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in comparisonRows"
                  :key="row.label"
                >
                  <td class="text-medium-emphasis">
                    {{ row.label }}
                  </td>
                  <td
                    v-for="(value, i) in row.values"
                    :key="i"
                    class="text-end front-mono"
                    :class="{ 'font-weight-bold': row.strong }"
                  >
                    {{ value }}
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VCol>
      </VRow>

      <p class="text-caption text-medium-emphasis mt-4">
        Comparaison indicative, hors assurance et frais de dossier. Pour un tableau
        d'amortissement détaillé, utilisez le
        <RouterLink to="/simulateur">
          simulateur complet
        </RouterLink>.
      </p>
    </div>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.comparator-page {
  min-block-size: 100vh;

  .comparator-title {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 700;
  }

  .comparator-card {
    overflow: hidden;
  }

  .comparator-add {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 2px dashed rgba(var(--v-border-color), calc(var(--v-border-opacity) * 2));
    cursor: pointer;
    min-block-size: 260px;
    transition: border-color 0.2s ease;

    &:hover {
      border-color: rgba(var(--v-theme-primary), 0.5);
    }
  }

  .comparator-table {
    td,
    th {
      white-space: nowrap;
    }
  }
}
</style>
