<!-- ❗Errors in the form are set on line 60 -->
<script setup>
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2RegisterIllustrationBorderedDark from '@images/pages/auth-v2-register-illustration-bordered-dark.png'
import authV2RegisterIllustrationBorderedLight from '@images/pages/auth-v2-register-illustration-bordered-light.png'
import authV2RegisterIllustrationDark from '@images/pages/auth-v2-register-illustration-dark.png'
import authV2RegisterIllustrationLight from '@images/pages/auth-v2-register-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { VForm } from 'vuetify/components/VForm'

const authThemeImg = useGenerateImageVariant(authV2RegisterIllustrationLight, authV2RegisterIllustrationDark, authV2RegisterIllustrationBorderedLight, authV2RegisterIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAbility()

const errors = ref({
  name: undefined,
  email: undefined,
  password: undefined,
})

const refVForm = ref()

const credentials = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const privacyPolicies = ref(false)
const loading = ref(false)

const register = async () => {
  loading.value = true
  try {
    const res = await $api('/auth/register', {
      method: 'POST',
      body: {
        name: credentials.value.name,
        email: credentials.value.email,
        password: credentials.value.password,
        password_confirmation: credentials.value.password_confirmation,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors || response._data
      },
    })

    const { userToken, user } = res.data

    useCookie('userAbilityRules').value = user.ability_rules
    ability.update(user.ability_rules)
    useCookie('userData').value = user
    useCookie('accessToken').value = userToken

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid && privacyPolicies.value)
      register()
  })
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            L'aventure commence ici 🚀
          </h4>
          <p class="mb-0">
            Gérez vos simulations de prêt en toute simplicité !
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- name -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.name"
                  label="Nom complet"
                  placeholder="John Doe"
                  autofocus
                  :rules="[requiredValidator]"
                  :error-messages="errors.name"
                />
              </VCol>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  label="Email"
                  placeholder="johndoe@email.com"
                  type="email"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  label="Mot de passe"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="new-password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>
              
              <!-- password confirm -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password_confirmation"
                  label="Confirmer le mot de passe"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  autocomplete="new-password"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap mt-2 mb-4">
                  <VCheckbox
                    v-model="privacyPolicies"
                    label="J'accepte la politique de confidentialité et les conditions d'utilisation"
                  />
                </div>

                <VBtn
                  block
                  type="submit"
                  :disabled="!privacyPolicies"
                  :loading="loading"
                  class="mt-2"
                >
                  S'inscrire
                  <template #loader>
                    <VProgressCircular indeterminate color="white" size="22" width="2" />
                  </template>
                </VBtn>
              </VCol>

              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">ou</span>
                <VDivider />
              </VCol>

              <!-- auth providers -->
              <VCol
                cols="12"
                class="text-center"
              >
                <AuthProvider />
              </VCol>
              
              <VCol cols="12" class="text-center">
                <span>Vous avez déjà un compte ?</span>
                <RouterLink
                  class="text-primary ms-2"
                  to="/login"
                >
                  Connectez-vous à la place
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
