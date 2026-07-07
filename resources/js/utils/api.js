import { ofetch } from 'ofetch'
import { router } from '@/plugins/1.router'

const clearSession = () => {
  useCookie('accessToken').value = null
  useCookie('userData').value = null
  useCookie('userAbilityRules').value = null
}

// Non authentifié (401) : purge de la session et retour à la page de connexion,
// en conservant la page demandée dans `?to=` pour y revenir après connexion.
export const handleUnauthenticated = () => {
  clearSession()

  const currentRoute = router.currentRoute.value
  if (currentRoute.name !== 'login') {
    router.replace({
      name: 'login',
      query: { to: currentRoute.fullPath !== '/' ? currentRoute.fullPath : undefined },
    })
  }
}

// Changement de mot de passe requis (403, sub_code 002) : redirection vers la
// page de sécurité du compte, seule page permettant de mettre à jour le mot de passe.
export const handlePasswordChangeRequired = () => {
  const userData = useCookie('userData')
  if (userData.value)
    userData.value = { ...userData.value, password_change_required: true }

  if (router.currentRoute.value.name !== 'account-security')
    router.replace({ name: 'account-security' })
}

export const handleApiError = (status, errors) => {
  if (status === 401)
    handleUnauthenticated()
  else if (status === 403 && errors?.password_change_required)
    handlePasswordChangeRequired()
}

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    if (accessToken)
      options.headers.append('Authorization', `Bearer ${accessToken}`)
  },
  async onResponseError({ response }) {
    handleApiError(response?.status, response?._data?.errors)
  },
})
