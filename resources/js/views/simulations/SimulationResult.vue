<script setup>
import { useTheme } from 'vuetify'
import { $api } from '@/utils/api'
import { formatDuration, formatXOF, methodLabel } from '@/utils/loan'

const props = defineProps({
  simulation: {
    type: Object,
    required: true,
  },

  // Affiche les boutons d'export / partage (nécessite d'être connecté + propriétaire)
  canManage: {
    type: Boolean,
    default: false,
  },
})

const vuetifyTheme = useTheme()

// 👉 Cartes résumé
const summaryCards = computed(() => {
  const s = props.simulation.summary
  const isConstantPayment = props.simulation.params.method === 'annuity'

  return [
    {
      icon: 'tabler-calendar-repeat',
      label: isConstantPayment ? 'Mensualité' : 'Première mensualité',
      value: formatXOF(s.monthly_payment),
      caption: isConstantPayment ? 'Constante sur toute la durée' : `Dernière : ${formatXOF(s.last_payment)}`,
      color: 'primary',
    },
    {
      icon: 'tabler-percentage',
      label: 'Total des intérêts',
      value: formatXOF(s.total_interest),
      caption: s.total_insurance > 0 ? `+ ${formatXOF(s.total_insurance)} d'assurance` : 'Hors assurance',
      color: 'warning',
    },
    {
      icon: 'tabler-sum',
      label: 'Coût total du crédit',
      value: formatXOF(s.total_cost),
      caption: `Soit ${formatXOF(s.total_paid)} remboursés au total`,
      color: 'error',
    },
    {
      icon: 'tabler-flag-check',
      label: 'Fin du prêt',
      value: new Date(s.end_date).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }),
      caption: formatDuration(props.simulation.params.duration_months),
      color: 'success',
    },
  ]
})

// 👉 Graphiques
const chartPalette = { capital: '#7367F0', interest: '#E8A33D', insurance: '#17897B' }

const hasInsurance = computed(() => (props.simulation.summary.total_insurance ?? 0) > 0)

const donutSeries = computed(() => {
  const s = props.simulation.summary

  return hasInsurance.value
    ? [s.total_principal, s.total_interest, s.total_insurance]
    : [s.total_principal, s.total_interest]
})

const donutOptions = computed(() => {
  const onSurface = `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.9)`

  return {
    chart: { parentHeightOffset: 0 },
    labels: hasInsurance.value ? ['Capital', 'Intérêts', 'Assurance'] : ['Capital', 'Intérêts'],
    colors: hasInsurance.value
      ? [chartPalette.capital, chartPalette.interest, chartPalette.insurance]
      : [chartPalette.capital, chartPalette.interest],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', labels: { colors: onSurface } },
    tooltip: { y: { formatter: v => formatXOF(v) } },
    states: { hover: { filter: { type: 'none' } } },
  }
})

const balanceSeries = computed(() => [{
  name: 'Solde restant dû',
  data: props.simulation.schedule.map(row => row.balance),
}])

const balanceOptions = computed(() => {
  const onSurfaceMuted = `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.6)`
  const schedule = props.simulation.schedule
  const step = Math.max(1, Math.round(schedule.length / 8))

  return {
    chart: { parentHeightOffset: 0, toolbar: { show: false }, zoom: { enabled: false } },
    colors: [chartPalette.capital],
    stroke: { curve: 'smooth', width: 3 },
    fill: {
      type: 'gradient',
      gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
    },
    dataLabels: { enabled: false },
    grid: { borderColor: `rgba(${vuetifyTheme.current.value.colors['on-surface']}, 0.12)` },
    xaxis: {
      categories: schedule.map(row => row.date),
      tickAmount: 8,
      labels: {
        rotate: 0,
        style: { colors: onSurfaceMuted },
        formatter: (value, _, opts) => {
          const index = typeof opts === 'object' && opts?.i !== undefined ? opts.i : schedule.findIndex(r => r.date === value)
          if (index % step !== 0)
            return ''

          return new Date(value).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
        },
      },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: onSurfaceMuted },
        formatter: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v),
      },
    },
    tooltip: {
      x: { formatter: v => new Date(schedule[v - 1]?.date ?? v).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) },
      y: { formatter: v => formatXOF(v) },
    },
  }
})

// 👉 Tableau d'amortissement
const tableHeaders = computed(() => [
  { title: 'N°', key: 'period', width: 60 },
  { title: 'Date', key: 'date' },
  { title: 'Capital', key: 'principal', align: 'end' },
  { title: 'Intérêts', key: 'interest', align: 'end' },
  ...(hasInsurance.value ? [{ title: 'Assurance', key: 'insurance', align: 'end' }] : []),
  { title: 'Mensualité', key: 'total', align: 'end' },
  { title: 'Solde restant', key: 'balance', align: 'end' },
])

const itemsPerPage = ref(12)
const tablePage = ref(1)

// 👉 Exports
const exportFormats = [
  { format: 'pdf', label: 'PDF', icon: 'tabler-file-type-pdf', ext: 'pdf' },
  { format: 'word', label: 'Word', icon: 'tabler-file-type-doc', ext: 'docx' },
  { format: 'excel', label: 'Excel', icon: 'tabler-file-type-xls', ext: 'xlsx' },
]

const exporting = ref(null)

const downloadExport = async ({ format, ext }) => {
  if (!props.simulation.id)
    return
  exporting.value = format
  try {
    const blob = await $api(`/simulations/${props.simulation.id}/export/${format}`, { responseType: 'blob' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = `simulation-pret-${props.simulation.id}.${ext}`
    link.click()
    URL.revokeObjectURL(url)
  }
  finally {
    exporting.value = null
  }
}

// 👉 Partage
const isShareDialogOpen = ref(false)
const shareUrl = ref(null)
const shareLoading = ref(false)
const shareCopied = ref(false)

const openShareDialog = async () => {
  isShareDialogOpen.value = true
  if (shareUrl.value)
    return
  shareLoading.value = true
  try {
    const res = await $api(`/simulations/${props.simulation.id}/share`, { method: 'POST' })

    shareUrl.value = res.data.share_url
  }
  finally {
    shareLoading.value = false
  }
}

const copyShareUrl = async () => {
  if (!shareUrl.value)
    return
  await navigator.clipboard.writeText(shareUrl.value)
  shareCopied.value = true
  setTimeout(() => (shareCopied.value = false), 2000)
}

const revokeShare = async () => {
  await $api(`/simulations/${props.simulation.id}/share`, { method: 'DELETE' })
  shareUrl.value = null
  isShareDialogOpen.value = false
}
</script>

<template>
  <div class="simulation-result">
    <!-- 👉 Cartes résumé -->
    <VRow class="mb-1">
      <VCol
        v-for="card in summaryCards"
        :key="card.label"
        cols="12"
        sm="6"
        lg="3"
      >
        <VCard
          variant="flat"
          class="simulator-summary-card h-100"
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
            <div class="front-mono simulator-summary-value">
              {{ card.value }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ card.caption }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Graphiques -->
    <VRow>
      <VCol
        cols="12"
        lg="5"
      >
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>Répartition du coût</VCardTitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="donut"
              height="280"
              :options="donutOptions"
              :series="donutSeries"
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
            <VCardTitle>Solde restant dû</VCardTitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="area"
              height="280"
              :options="balanceOptions"
              :series="balanceSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Tableau -->
    <VCard class="mt-6">
      <VCardItem>
        <div class="d-flex flex-wrap justify-space-between align-center gap-4">
          <div>
            <VCardTitle>Tableau d'amortissement</VCardTitle>
            <VCardSubtitle>
              {{ methodLabel(simulation.params.method) }} — {{ formatXOF(simulation.params.amount) }}
              sur {{ formatDuration(simulation.params.duration_months) }} à {{ simulation.params.annual_rate.toLocaleString('fr-FR') }} %
            </VCardSubtitle>
          </div>

          <div
            v-if="canManage"
            class="d-flex align-center flex-wrap gap-2"
          >
            <VBtn
              v-for="fmt in exportFormats"
              :key="fmt.format"
              size="small"
              variant="tonal"
              :prepend-icon="fmt.icon"
              :loading="exporting === fmt.format"
              @click="downloadExport(fmt)"
            >
              {{ fmt.label }}
            </VBtn>
            <VBtn
              size="small"
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-share"
              @click="openShareDialog"
            >
              Partager
            </VBtn>
          </div>
          <slot
            v-else
            name="actions"
          />
        </div>
      </VCardItem>

      <VDataTable
        v-model:page="tablePage"
        v-model:items-per-page="itemsPerPage"
        :headers="tableHeaders"
        :items="simulation.schedule"
        :items-per-page-options="[12, 24, 60, { value: -1, title: 'Tout' }]"
        density="comfortable"
        class="simulator-table front-mono"
      >
        <template #item.date="{ item }">
          {{ new Date(item.date).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' }) }}
        </template>
        <template #item.principal="{ item }">
          {{ formatXOF(item.principal) }}
        </template>
        <template #item.interest="{ item }">
          {{ formatXOF(item.interest) }}
        </template>
        <template #item.insurance="{ item }">
          {{ formatXOF(item.insurance) }}
        </template>
        <template #item.total="{ item }">
          <span class="font-weight-bold">{{ formatXOF(item.total) }}</span>
        </template>
        <template #item.balance="{ item }">
          {{ formatXOF(item.balance) }}
        </template>
      </VDataTable>
    </VCard>

    <!-- 👉 Dialog de partage -->
    <VDialog
      v-model="isShareDialogOpen"
      max-width="520"
    >
      <VCard>
        <VCardItem>
          <VCardTitle>Partager la simulation</VCardTitle>
          <VCardSubtitle>Toute personne disposant du lien pourra consulter cette simulation.</VCardSubtitle>
        </VCardItem>
        <VCardText>
          <div
            v-if="shareLoading"
            class="text-center py-4"
          >
            <VProgressCircular
              indeterminate
              color="primary"
            />
          </div>
          <template v-else-if="shareUrl">
            <AppTextField
              :model-value="shareUrl"
              readonly
              :append-inner-icon="shareCopied ? 'tabler-check' : 'tabler-copy'"
              @click:append-inner="copyShareUrl"
            />
            <p
              v-if="shareCopied"
              class="text-caption text-success mt-1 mb-0"
            >
              Lien copié !
            </p>
          </template>
        </VCardText>
        <VCardText class="d-flex justify-space-between">
          <VBtn
            color="error"
            variant="outlined"
            size="small"
            prepend-icon="tabler-link-off"
            :disabled="!shareUrl"
            @click="revokeShare"
          >
            Désactiver le lien
          </VBtn>
          <VBtn
            size="small"
            variant="tonal"
            @click="isShareDialogOpen = false"
          >
            Fermer
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss">
.simulation-result {
  .simulator-summary-card {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .simulator-summary-value {
    font-size: 1.15rem;
    font-weight: 600;
    line-height: 1.5;
  }

  .simulator-table {
    font-size: 0.85rem;

    th {
      font-family: "Public Sans", sans-serif;
      white-space: nowrap;
    }
  }
}
</style>
