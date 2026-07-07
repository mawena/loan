<script setup>
import SimulationResult from '@/views/simulations/SimulationResult.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Auth',
  },
})

const route = useRoute()
const router = useRouter()

const simulation = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await $api(`/simulations/${route.params.id}`)

    simulation.value = res.data
  }
  catch {
    showSnackbar('Simulation introuvable', 'error')
    router.replace('/mes-simulations')
  }
  finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h1 class="text-h4 mb-1">
          {{ simulation?.title || (simulation ? `Simulation #${simulation.id}` : 'Simulation') }}
        </h1>
        <p
          v-if="simulation"
          class="text-body-2 text-medium-emphasis mb-0"
        >
          Créée le {{ new Date(simulation.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
        </p>
      </div>
      <VBtn
        variant="tonal"
        prepend-icon="tabler-arrow-left"
        to="/mes-simulations"
      >
        Retour à l'historique
      </VBtn>
    </div>

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

    <SimulationResult
      v-else-if="simulation"
      :simulation="simulation"
      can-manage
    />
  </div>
</template>
