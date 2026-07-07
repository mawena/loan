<script setup>
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import { formatDuration, formatXOF, LOAN_METHODS, methodLabel } from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    action: 'read',
    subject: 'simulation',
  },
})

const headers = [
  { title: 'Simulation', key: 'title' },
  { title: 'Montant', key: 'amount', sortable: false },
  { title: 'Durée', key: 'duration', sortable: false },
  { title: 'Taux', key: 'rate', sortable: false },
  { title: 'Méthode', key: 'method', sortable: false },
  { title: 'Mensualité', key: 'payment', sortable: false },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: -1, title: 'Tout' },
]

const loading = ref(false)
const allSimulations = ref([])

// ----- Filtres (frontend) -----
const search = ref('')
const selectedMethod = ref(null)

const methodOptions = LOAN_METHODS.map(m => ({ title: m.title, value: m.value }))

const filteredSimulations = computed(() => allSimulations.value.filter(simulation => {
  const matchesSearchQuery = matchesSearch(simulation, ['title'], search.value)
  const matchesMethod = !selectedMethod.value || simulation.params?.method === selectedMethod.value

  return matchesSearchQuery && matchesMethod
}))

// ----- Tri & pagination (frontend) -----
const {
  page,
  itemsPerPage,
  updateOptions,
  paginatedItems: simulations,
  totalItems: totalSimulations,
} = useClientTable(filteredSimulations)

const fetchSimulations = async () => {
  loading.value = true
  try {
    const res = await $api('/simulations')

    allSimulations.value = res.data
  }
  catch (err) {
    showSnackbar(err?.data?.errors?.auth || 'Erreur lors du chargement des simulations', 'error')
  }
  finally {
    loading.value = false
  }
}

onMounted(fetchSimulations)

// ----- Suppression avec animation -----
const deletingIds = reactive(new Set())
const rowProps = ({ item }) => ({ class: deletingIds.has(item.id) ? 'row-removing' : undefined })
const ROW_REMOVE_ANIMATION_DURATION = 300

const isDeleteDialogOpen = ref(false)
const simulationToDelete = ref(null)

const confirmDelete = simulation => {
  simulationToDelete.value = simulation
  isDeleteDialogOpen.value = true
}

const deleteSimulation = async () => {
  const simulation = simulationToDelete.value

  isDeleteDialogOpen.value = false
  if (!simulation)
    return

  try {
    await $api(`/simulations/${simulation.id}`, { method: 'DELETE' })
    deletingIds.add(simulation.id)
    await new Promise(resolve => setTimeout(resolve, ROW_REMOVE_ANIMATION_DURATION))
    allSimulations.value = allSimulations.value.filter(s => s.id !== simulation.id)
    deletingIds.delete(simulation.id)
    showSnackbar('Simulation supprimée', 'success')
  }
  catch {
    showSnackbar('Erreur lors de la suppression', 'error')
  }
}

// ----- Export rapide -----
const exportFormats = [
  { format: 'pdf', label: 'PDF', icon: 'tabler-file-type-pdf', ext: 'pdf' },
  { format: 'word', label: 'Word', icon: 'tabler-file-type-doc', ext: 'docx' },
  { format: 'excel', label: 'Excel', icon: 'tabler-file-type-xls', ext: 'xlsx' },
]

const downloadExport = async (simulation, { format, ext }) => {
  try {
    const blob = await $api(`/simulations/${simulation.id}/export/${format}`, { responseType: 'blob' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = `simulation-pret-${simulation.id}.${ext}`
    link.click()
    URL.revokeObjectURL(url)
  }
  catch {
    showSnackbar('Erreur lors du téléchargement', 'error')
  }
}
</script>

<template>
  <div class="front-page mes-simulations-page">
    <FrontNavbar />
    
    <div class="front-container py-8">
    <VCard>
      <VCardItem>
        <VCardTitle>Mes simulations</VCardTitle>
        <VCardSubtitle>Retrouvez, re-téléchargez ou supprimez vos simulations de prêt.</VCardSubtitle>
      </VCardItem>

      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="5"
          >
            <AppTextField
              v-model="search"
              placeholder="Rechercher par nom..."
              prepend-inner-icon="tabler-search"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedMethod"
              :items="methodOptions"
              placeholder="Méthode d'amortissement"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            sm="3"
            class="d-flex justify-end align-center gap-2"
          >
            <VBtn
              variant="tonal"
              prepend-icon="tabler-refresh"
              :loading="loading"
              @click="fetchSimulations"
            >
              Recharger
            </VBtn>
            <VBtn
              prepend-icon="tabler-plus"
              to="/simulateur"
            >
              Nouvelle
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDataTableServer
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="simulations"
        :items-length="totalSimulations"
        :items-per-page-options="itemsPerPageOptions"
        :loading="loading"
        :row-props="rowProps"
        @update:options="updateOptions"
      >
        <template #item.title="{ item }">
          <RouterLink
            :to="`/mes-simulations/${item.id}`"
            class="text-link font-weight-medium"
          >
            {{ item.title || `Simulation #${item.id}` }}
          </RouterLink>
        </template>

        <template #item.amount="{ item }">
          <span class="text-no-wrap">{{ formatXOF(item.params?.amount) }}</span>
        </template>

        <template #item.duration="{ item }">
          {{ formatDuration(item.params?.duration_months ?? 0) }}
        </template>

        <template #item.rate="{ item }">
          {{ (item.params?.annual_rate ?? 0).toLocaleString('fr-FR') }} %
        </template>

        <template #item.method="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ methodLabel(item.params?.method) }}
          </VChip>
        </template>

        <template #item.payment="{ item }">
          <span class="text-no-wrap font-weight-medium">{{ formatXOF(item.summary?.monthly_payment) }}</span>
        </template>

        <template #item.created_at="{ item }">
          {{ new Date(item.created_at).toLocaleDateString('fr-FR') }}
        </template>

        <template #item.actions="{ item }">
          <IconBtn :to="`/mes-simulations/${item.id}`">
            <VIcon icon="tabler-eye" />
            <VTooltip activator="parent">
              Revoir
            </VTooltip>
          </IconBtn>

          <IconBtn>
            <VIcon icon="tabler-download" />
            <VTooltip activator="parent">
              Télécharger
            </VTooltip>
            <VMenu activator="parent">
              <VList density="compact">
                <VListItem
                  v-for="fmt in exportFormats"
                  :key="fmt.format"
                  :prepend-icon="fmt.icon"
                  @click="downloadExport(item, fmt)"
                >
                  <VListItemTitle>{{ fmt.label }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </IconBtn>

          <IconBtn @click="confirmDelete(item)">
            <VIcon
              icon="tabler-trash"
              color="error"
            />
            <VTooltip activator="parent">
              Supprimer
            </VTooltip>
          </IconBtn>
        </template>

        <template #no-data>
          <div class="text-center py-10">
            <p class="text-body-1 mb-3">
              Aucune simulation enregistrée pour le moment.
            </p>
            <VBtn
              to="/simulateur"
              prepend-icon="tabler-calculator"
            >
              Lancer ma première simulation
            </VBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Dialog de confirmation de suppression -->
    <VDialog
      v-model="isDeleteDialogOpen"
      max-width="440"
    >
      <VCard>
        <VCardItem>
          <VCardTitle>Supprimer la simulation ?</VCardTitle>
        </VCardItem>
        <VCardText>
          « {{ simulationToDelete?.title || `Simulation #${simulationToDelete?.id}` }} » sera
          définitivement supprimée, ainsi que son lien de partage éventuel.
        </VCardText>
        <VCardText class="d-flex justify-end gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDeleteDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            @click="deleteSimulation"
          >
            Supprimer
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    </div>

    <FrontFooter />
  </div>
</template>

<style scoped lang="scss">
.text-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

:deep(.row-removing) {
  opacity: 0;
  transition: opacity 0.3s ease;
}

.mes-simulations-page {
  min-block-size: 100vh;
}
</style>
