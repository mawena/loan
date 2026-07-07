<script setup>
const router = useRouter()
const ability = useAbility()
const userData = useCookie('userData')

const avatarText = computed(() => {
  if (!userData.value?.name) return '?'
  
  return userData.value.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

const isAdmin = computed(() => ability.can('manage', 'all'))
const roleColor = computed(() => isAdmin.value ? 'error' : 'primary')

const logout = async () => {
  try {
    await $api('/auth/logout', { method: 'DELETE' })
  } catch {}

  useCookie('accessToken').value = null
  userData.value = null
  useCookie('userAbilityRules').value = null
  ability.update([])

  router.push('/login')
}
</script>

<template>
  <VAvatar
    v-if="userData"
    size="38"
    :color="roleColor"
    variant="tonal"
    class="cursor-pointer"
  >
    <span class="text-caption font-weight-bold">{{ avatarText }}</span>

    <VMenu
      activator="parent"
      width="220"
      location="bottom end"
      offset="12px"
    >
      <VList>
        <!-- User info header -->
        <VListItem class="pb-0">
          <div class="d-flex align-center gap-3 py-1">
            <VAvatar
              :color="roleColor"
              size="40"
            >
              <span class="font-weight-bold text-white text-body-2">{{ avatarText }}</span>
            </VAvatar>
            <div class="min-w-0">
              <div class="font-weight-bold text-body-2 text-truncate">
                {{ userData.name }}
              </div>
              <div class="text-caption text-truncate text-medium-emphasis">
                {{ userData.email }}
              </div>
              <VChip
                :color="roleColor"
                size="x-small"
                label
                class="mt-1"
              >
                <VIcon
                  start
                  :icon="isAdmin ? 'tabler-shield-filled' : 'tabler-user'"
                  size="11"
                />
                {{ isAdmin ? 'Administrateur' : 'Utilisateur' }}
              </VChip>
            </div>
          </div>
        </VListItem>

        <VDivider class="my-2" />

        <!-- Change password -->
        <VListItem
          :to="{ name: 'account-security' }"
          prepend-icon="tabler-lock"
        >
          <VListItemTitle>Changer le mot de passe</VListItemTitle>
        </VListItem>

        <VDivider class="my-2" />

        <!-- Logout -->
        <VListItem
          class="text-error"
          prepend-icon="tabler-logout"
          @click="logout"
        >
          <VListItemTitle class="text-error font-weight-medium">
            Déconnexion
          </VListItemTitle>
        </VListItem>
      </VList>
    </VMenu>
  </VAvatar>
</template>

<style scoped>
.min-w-0 { min-width: 0; }
</style>
