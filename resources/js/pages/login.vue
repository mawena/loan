<!-- ❗Errors in the form are set on line 60 -->
<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { VForm } from 'vuetify/components/VForm'

const authThemeImg = useGenerateImageVariant(authV2LoginIllustrationLight, authV2LoginIllustrationDark, authV2LoginIllustrationBorderedLight, authV2LoginIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAbility()

const errors = ref({
  email: undefined,
  password: undefined,
})

const refVForm = ref()

const credentials = ref({
  email: '',
  password: '',
})

const rememberMe = ref(false)
const loading = ref(false)

const login = async () => {
  loading.value = true
  try {
    const res = await $api('/auth/login', {
      method: 'POST',
      body: {
        email: credentials.value.email,
        password: credentials.value.password,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    const { userToken, user } = res.data

    useCookie('userAbilityRules').value = user.ability_rules
    ability.update(user.ability_rules)
    useCookie('userData').value = user
    useCookie('accessToken').value = userToken

    await nextTick(() => {
      if (route.query.to) {
        router.replace(String(route.query.to))
      } else if (ability.can('manage', 'all')) {
        router.replace('/admin/users')
      } else {
        router.replace('/')
      }
    })
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Connexion via Portier (facultative, PORTIER_ACTIF dans .env)
const portierActif = window.portierActif === true
const portierErreur = ref('')

const messagesPortier = {
  etat: 'La connexion via Portier a expiré ou a été interrompue. Réessayez.',
  refus: 'Portier a refusé l\'accès à cette application.',
  sans_email: 'Votre compte Portier n\'a pas d\'adresse e-mail vérifiée.',
  compte_inconnu: 'Aucun compte Loan ne correspond à votre adresse e-mail Portier.',
  echec: 'La connexion via Portier a échoué. Réessayez ou utilisez votre mot de passe.',
}

const connexionPortier = async code => {
  loading.value = true
  try {
    const res = await $api('/auth/portier/echange', {
      method: 'POST',
      body: { code },
    })

    const { userToken, user } = res.data

    useCookie('userAbilityRules').value = user.ability_rules
    ability.update(user.ability_rules)
    useCookie('userData').value = user
    useCookie('accessToken').value = userToken

    await nextTick(() => {
      router.replace(ability.can('manage', 'all') ? '/admin/users' : '/')
    })
  } catch (err) {
    console.error(err)
    portierErreur.value = messagesPortier.echec
    router.replace({ query: {} })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (route.query.portier_erreur) {
    portierErreur.value = messagesPortier[String(route.query.portier_erreur)] ?? messagesPortier.echec
    router.replace({ query: {} })
  } else if (route.query.portier) {
    connexionPortier(String(route.query.portier))
  }
})

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      login()
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

  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 6.25rem;">
          <VImg max-width="613" :src="authThemeImg" class="auth-illustration mt-16 mb-2" />
        </div>

        <img class="auth-footer-mask" :src="authThemeMask" alt="auth-footer-mask" height="280" width="100">
      </div>
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-4">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Bienvenue sur <span class="text-capitalize"> {{ themeConfig.app.title }} </span>! 👋🏻
          </h4>
          <p class="mb-0">
            Veuillez vous connecter à votre compte pour commencer l'aventure
          </p>
        </VCardText>
        <VCardText>
          <VAlert v-if="portierErreur" type="error" variant="tonal" class="mb-4" closable @click:close="portierErreur = ''">
            {{ portierErreur }}
          </VAlert>
          <VForm ref="refVForm" @submit.prevent="onSubmit">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField v-model="credentials.email" label="Email" placeholder="johndoe@email.com" type="email"
                  autofocus :rules="[requiredValidator, emailValidator]" :error-messages="errors.email" />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField v-model="credentials.password" label="Mot de passe" placeholder="············"
                  :rules="[requiredValidator]" :type="isPasswordVisible ? 'text' : 'password'" autocomplete="password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" />

                <div class="d-flex align-center justify-space-between flex-wrap mt-2 mb-4">
                  <VCheckbox v-model="rememberMe" label="Se souvenir de moi" />
                  <a class="text-primary cursor-pointer" href="javascript:void(0)">Mot de passe oublié ?</a>
                </div>

                <VBtn block type="submit" class="mt-2" :loading="loading">
                  Se connecter
                  <template #loader>
                    <VProgressCircular indeterminate color="white" size="22" width="2" />
                  </template>
                </VBtn>
              </VCol>

              <template v-if="portierActif">
                <VCol cols="12" class="d-flex align-center">
                  <VDivider />
                  <span class="mx-4">ou</span>
                  <VDivider />
                </VCol>
                <VCol cols="12">
                  <VBtn block variant="tonal" href="/auth/portier" prepend-icon="tabler-shield-lock" :disabled="loading">
                    Se connecter via Portier
                  </VBtn>
                </VCol>
              </template>

              <VCol cols="12" class="text-center">
                <span>Nouveau sur notre plateforme ?</span>
                <RouterLink class="text-primary ms-2" to="/register">
                  Créer un compte
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
