<script setup>
definePage({
  meta: {
    // Accessible à tout utilisateur connecté (client ou admin)
    action: 'read',
    subject: 'Auth',
  },
})

const userData = useCookie('userData')
const ability = useAbility()
const router = useRouter()

const form = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const errors = ref({})
const success = ref(false)
const saving = ref(false)
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const submit = async () => {
  saving.value = true
  errors.value = {}
  success.value = false

  try {
    const res = await $api('/users/update-password', {
      method: 'PUT',
      body: form.value,
      onResponseError({ response }) {
        errors.value = response._data.errors || {}
      },
    })

    // Le mot de passe a été changé : le changement n'est plus requis.
    if (userData.value) {
      userData.value = { ...userData.value, password_change_required: false }
    }

    success.value = true
    form.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  }
  catch (err) {
    if (!Object.keys(errors.value).length)
      errors.value._general = err?.data?.message || 'Une erreur est survenue'
  }
  finally {
    saving.value = false
  }
}

const goBack = () => {
  router.push('/')
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center gap-3 mb-6">
      <VBtn
        icon
        variant="text"
        @click="goBack"
      >
        <VIcon icon="tabler-arrow-left" />
      </VBtn>
      <div>
        <h2 class="text-h5 font-weight-bold">
          Sécurité du compte
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Connecté en tant que <strong>{{ userData?.name }}</strong>
          <VChip
            size="x-small"
            :color="ability.can('manage', 'all') ? 'error' : 'primary'"
            class="ms-2"
            label
          >
            {{ ability.can('manage', 'all') ? 'Administrateur' : 'Utilisateur' }}
          </VChip>
        </p>
      </div>
    </div>

    <VRow justify="center">
      <VCol
        cols="12"
        sm="10"
        md="7"
        lg="5"
      >
        <!-- Change password card -->
        <VCard>
          <VCardTitle class="pa-5 d-flex align-center gap-2">
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
              rounded
            >
              <VIcon icon="tabler-lock" />
            </VAvatar>
            <div>
              <div class="font-weight-bold">
                Changer le mot de passe
              </div>
              <div class="text-caption text-medium-emphasis">
                Choisissez un mot de passe fort
              </div>
            </div>
          </VCardTitle>
          <VDivider />

          <VCardText class="pa-5">
            <VAlert
              v-if="success"
              type="success"
              variant="tonal"
              class="mb-5"
              closable
              @click:close="success = false"
            >
              <strong>Mot de passe modifié avec succès !</strong>
              Les autres sessions ont été déconnectées.
            </VAlert>

            <VAlert
              v-if="errors._general"
              type="error"
              variant="tonal"
              class="mb-5"
            >
              {{ errors._general }}
            </VAlert>

            <VTextField
              v-model="form.current_password"
              label="Mot de passe actuel"
              :type="showCurrent ? 'text' : 'password'"
              :append-inner-icon="showCurrent ? 'tabler-eye-off' : 'tabler-eye'"
              prepend-inner-icon="tabler-lock"
              :error-messages="errors.current_password"
              class="mb-4"
              @click:append-inner="showCurrent = !showCurrent"
            />

            <VDivider class="mb-4" />

            <VTextField
              v-model="form.new_password"
              label="Nouveau mot de passe"
              :type="showNew ? 'text' : 'password'"
              :append-inner-icon="showNew ? 'tabler-eye-off' : 'tabler-eye'"
              prepend-inner-icon="tabler-lock-plus"
              :error-messages="errors.new_password"
              hint="Minimum 8 caractères"
              persistent-hint
              class="mb-4"
              @click:append-inner="showNew = !showNew"
            />

            <VTextField
              v-model="form.new_password_confirmation"
              label="Confirmer le nouveau mot de passe"
              :type="showConfirm ? 'text' : 'password'"
              :append-inner-icon="showConfirm ? 'tabler-eye-off' : 'tabler-eye'"
              prepend-inner-icon="tabler-lock-check"
              :error-messages="errors.new_password_confirmation"
              class="mb-2"
              @click:append-inner="showConfirm = !showConfirm"
            />
          </VCardText>

          <VDivider />
          <VCardActions class="pa-4">
            <VSpacer />
            <VBtn
              color="primary"
              :loading="saving"
              :disabled="!form.current_password || !form.new_password || !form.new_password_confirmation"
              @click="submit"
            >
              <VIcon
                start
                icon="tabler-device-floppy"
              />
              Enregistrer
            </VBtn>
          </VCardActions>
        </VCard>

        <!-- Info card -->
        <VCard
          class="mt-4"
          variant="tonal"
          color="info"
        >
          <VCardText class="pa-4">
            <div class="d-flex gap-3">
              <VIcon
                icon="tabler-info-circle"
                color="info"
                class="mt-1 flex-shrink-0"
              />
              <div class="text-body-2">
                <strong>Conseils de sécurité :</strong>
                <ul class="mt-1 ps-4">
                  <li>Utilisez au moins 8 caractères</li>
                  <li>Mélangez lettres, chiffres et symboles</li>
                  <li>N'utilisez pas votre numéro de téléphone</li>
                  <li>Ne partagez jamais votre mot de passe</li>
                </ul>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
