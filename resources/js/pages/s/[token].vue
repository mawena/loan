<script setup>
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import SimulationResult from '@/views/simulations/SimulationResult.vue'
import { $api } from '@/utils/api'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()

const simulation = ref(null)
const loading = ref(true)
const notFound = ref(false)

onMounted(async () => {
  try {
    const res = await $api(`/shared/${route.params.token}`)

    simulation.value = res.data
  }
  catch {
    notFound.value = true
  }
  finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="front-page shared-page">
    <FrontNavbar />

    <div class="front-container py-8">
      <div
        v-if="loading"
        class="text-center py-16"
      >
        <VProgressCircular
          indeterminate
          color="primary"
          size="48"
        />
      </div>

      <template v-else-if="simulation">
        <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
          <div>
            <VChip
              size="small"
              color="info"
              variant="tonal"
              prepend-icon="tabler-share"
              class="mb-2"
            >
              Simulation partagée
            </VChip>
            <h1 class="front-display shared-title mb-1">
              {{ simulation.title || 'Simulation de prêt' }}
            </h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Consultation en lecture seule — créée le
              {{ new Date(simulation.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </p>
          </div>
          <VBtn
            prepend-icon="tabler-calculator"
            to="/simulateur"
          >
            Faire ma propre simulation
          </VBtn>
        </div>

        <SimulationResult :simulation="simulation" />
      </template>

      <!-- 👉 Lien invalide -->
      <VCard
        v-else
        variant="flat"
        class="text-center py-16 shared-not-found"
      >
        <VCardText>
          <VAvatar
            color="error"
            variant="tonal"
            size="72"
            class="mb-4"
          >
            <VIcon
              icon="tabler-link-off"
              size="40"
            />
          </VAvatar>
          <h2 class="text-h5 front-display mb-2">
            Ce lien de partage n'est plus actif
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-6">
            Le propriétaire a peut-être désactivé le partage, ou le lien est incorrect.
          </p>
          <VBtn
            to="/simulateur"
            prepend-icon="tabler-calculator"
          >
            Créer ma propre simulation
          </VBtn>
        </VCardText>
      </VCard>
    </div>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.shared-page {
  min-block-size: 100vh;

  .shared-title {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 700;
  }

  .shared-not-found {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 2px dashed rgba(var(--v-border-color), calc(var(--v-border-opacity) * 2));
  }
}
</style>
