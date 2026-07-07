<script setup>
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import SimulationResult from '@/views/simulations/SimulationResult.vue'
import { $api } from '@/utils/api'
import { formatDuration, LOAN_METHODS } from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()
const userData = useCookie('userData')

// 👉 Formulaire (préremplissable via query string depuis la page d'accueil)
const form = ref({
  title: '',
  amount: Number(route.query.amount) || 5000000,
  annual_rate: Number(route.query.rate) || 8,
  duration_months: Number(route.query.months) || 60,
  method: LOAN_METHODS.some(m => m.value === route.query.method) ? route.query.method : 'annuity',
  insurance_rate: null,
  start_date: null,
})

const refVForm = ref()
const loading = ref(false)
const errors = ref({})
const result = ref(null)
const resultsSection = ref(null)

// 👉 Quota
const quota = ref(null)

const fetchQuota = async () => {
  try {
    const res = await $api('/simulations/quota')

    quota.value = res.data
  }
  catch {
    quota.value = null
  }
}

onMounted(fetchQuota)

const quotaLabel = computed(() => {
  if (!quota.value)
    return null
  if (quota.value.plan === 'premium')
    return 'Simulations illimitées (Premium)'

  return `${quota.value.remaining} simulation${quota.value.remaining > 1 ? 's' : ''} restante${quota.value.remaining > 1 ? 's' : ''} aujourd'hui`
})

const quotaExceeded = computed(() => quota.value && !quota.value.allowed)

// 👉 Soumission
const simulate = async () => {
  loading.value = true
  errors.value = {}
  try {
    const res = await $api('/simulations', {
      method: 'POST',
      body: {
        ...form.value,
        title: form.value.title || null,
        insurance_rate: form.value.insurance_rate || null,
        start_date: form.value.start_date || null,
      },
      onResponseError({ response }) {
        errors.value = response._data?.errors ?? {}
      },
    })

    result.value = res.data.simulation
    quota.value = res.data.quota

    await nextTick()
    resultsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
  catch {
    // erreurs déjà capturées dans `errors`
  }
  finally {
    loading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid }) => {
    if (valid)
      simulate()
  })
}
</script>

<template>
  <div class="front-page simulator-page">
    <FrontNavbar />

    <div class="front-container py-8">
      <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
        <div>
          <h1 class="front-display simulator-title mb-1">
            Simulateur de prêt
          </h1>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Renseignez votre prêt pour obtenir le tableau d'amortissement complet.
          </p>
        </div>
        <VChip
          v-if="quotaLabel"
          :color="quotaExceeded ? 'error' : 'primary'"
          variant="tonal"
          prepend-icon="tabler-gauge"
        >
          {{ quotaLabel }}
        </VChip>
      </div>

      <VAlert
        v-if="errors.quota"
        type="warning"
        variant="tonal"
        class="mb-6"
        closable
      >
        <p class="mb-2">
          {{ errors.quota }}
        </p>
        <div class="d-flex gap-2">
          <VBtn
            v-if="!userData"
            size="small"
            to="/login"
          >
            Se connecter
          </VBtn>
          <VBtn
            size="small"
            variant="outlined"
            :to="{ path: '/', hash: '#tarifs' }"
          >
            Voir le Premium
          </VBtn>
        </div>
      </VAlert>

      <VRow>
        <!-- 👉 Formulaire -->
        <VCol
          cols="12"
          md="4"
        >
          <VCard class="simulator-form-card">
            <div class="kente-band" />
            <VCardText class="pa-6">
              <VForm
                ref="refVForm"
                @submit.prevent="onSubmit"
              >
                <VRow>
                  <VCol cols="12">
                    <AppTextField
                      v-model="form.title"
                      label="Nom de la simulation (optionnel)"
                      placeholder="Ex : Achat terrain Lomé"
                      :error-messages="errors.title"
                    />
                  </VCol>

                  <VCol cols="12">
                    <AppTextField
                      v-model.number="form.amount"
                      label="Montant du prêt"
                      type="number"
                      suffix="FCFA"
                      :rules="[requiredValidator]"
                      :error-messages="errors.amount"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <AppTextField
                      v-model.number="form.annual_rate"
                      label="Taux annuel"
                      type="number"
                      step="0.01"
                      suffix="%"
                      :rules="[requiredValidator]"
                      :error-messages="errors.annual_rate"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <AppTextField
                      v-model.number="form.duration_months"
                      label="Durée"
                      type="number"
                      suffix="mois"
                      :rules="[requiredValidator]"
                      :error-messages="errors.duration_months"
                      :hint="formatDuration(form.duration_months || 0)"
                      persistent-hint
                    />
                  </VCol>

                  <VCol cols="12">
                    <VLabel class="text-body-2 font-weight-medium mb-2">
                      Méthode d'amortissement
                    </VLabel>
                    <VRadioGroup
                      v-model="form.method"
                      :error-messages="errors.method"
                    >
                      <VRadio
                        v-for="m in LOAN_METHODS"
                        :key="m.value"
                        :value="m.value"
                      >
                        <template #label>
                          <div>
                            <span class="d-block font-weight-medium">{{ m.title }}</span>
                            <span class="d-block text-caption text-medium-emphasis">{{ m.hint }}</span>
                          </div>
                        </template>
                      </VRadio>
                    </VRadioGroup>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <AppTextField
                      v-model.number="form.insurance_rate"
                      label="Assurance (optionnel)"
                      type="number"
                      step="0.01"
                      suffix="%/an"
                      :error-messages="errors.insurance_rate"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <AppTextField
                      v-model="form.start_date"
                      label="Première échéance"
                      type="month"
                      :error-messages="errors.start_date"
                    />
                  </VCol>

                  <VCol cols="12">
                    <VBtn
                      block
                      size="large"
                      type="submit"
                      :loading="loading"
                      :disabled="quotaExceeded"
                      append-icon="tabler-calculator"
                    >
                      Calculer mon prêt
                    </VBtn>
                    <p
                      v-if="!userData"
                      class="text-caption text-medium-emphasis text-center mt-2 mb-0"
                    >
                      <RouterLink to="/login">
                        Connectez-vous
                      </RouterLink>
                      pour sauvegarder et télécharger vos simulations.
                    </p>
                  </VCol>
                </VRow>
              </VForm>
            </VCardText>
          </VCard>
        </VCol>

        <!-- 👉 Résultats -->
        <VCol
          cols="12"
          md="8"
        >
          <div ref="resultsSection">
            <SimulationResult
              v-if="result"
              :simulation="result"
              :can-manage="!!userData"
            >
              <template #actions>
                <VTooltip
                  location="top"
                  text="Connectez-vous pour télécharger en PDF, Word ou Excel"
                >
                  <template #activator="{ props: tooltipProps }">
                    <div v-bind="tooltipProps">
                      <VBtn
                        size="small"
                        variant="tonal"
                        prepend-icon="tabler-file-download"
                        disabled
                      >
                        Exporter
                      </VBtn>
                    </div>
                  </template>
                </VTooltip>
              </template>
            </SimulationResult>

            <!-- 👉 État vide -->
            <VCard
              v-else
              variant="flat"
              class="simulator-empty d-flex align-center justify-center"
            >
              <VCardText class="text-center py-16">
                <VAvatar
                  color="primary"
                  variant="tonal"
                  size="72"
                  class="mb-4"
                >
                  <VIcon
                    icon="tabler-table"
                    size="40"
                  />
                </VAvatar>
                <h3 class="text-h5 front-display mb-2">
                  Votre tableau apparaîtra ici
                </h3>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  Remplissez le formulaire puis cliquez sur « Calculer mon prêt »
                  pour obtenir mensualités, graphiques et échéancier complet.
                </p>
              </VCardText>
            </VCard>
          </div>
        </VCol>
      </VRow>
    </div>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.simulator-page {
  min-block-size: 100vh;

  .simulator-title {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 700;
  }

  .simulator-form-card {
    inset-block-start: 84px;
    overflow: hidden;
    position: sticky;
  }

  .simulator-empty {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 2px dashed rgba(var(--v-border-color), calc(var(--v-border-opacity) * 2));
    min-block-size: 420px;
  }
}
</style>
