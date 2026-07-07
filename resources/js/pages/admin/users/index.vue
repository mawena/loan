<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

const ability = useAbility()
const userData = useCookie('userData')

const headers = [
  { title: 'Utilisateur', key: 'name' },
  { title: 'Rôles', key: 'roles', sortable: false },
  { title: 'Statut', key: 'activated' },
  { title: 'Mot de passe', key: 'password_change_required', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: -1, title: 'Tout' },
]

const statusOptions = [
  { title: 'Actif', value: 1 },
  { title: 'Inactif', value: 0 },
]

const loading = ref(false)
const allUsers = ref([])
const roleOptions = ref([])

// ----- Filtres (frontend) -----
const search = ref('')
const selectedRole = ref(null)
const selectedStatus = ref(null)

const filteredUsers = computed(() => allUsers.value.filter(user => {
  const matchesSearchQuery = matchesSearch(user, ['name', 'email'], search.value)
  const matchesRole = !selectedRole.value || user.roles?.some(role => role.id === selectedRole.value)
  const matchesStatus = selectedStatus.value === null || !!user.activated === !!selectedStatus.value

  return matchesSearchQuery && matchesRole && matchesStatus
}))

// ----- Tri & pagination (frontend) -----
const {
  page,
  itemsPerPage,
  updateOptions,
  paginatedItems: users,
  totalItems: totalUsers,
} = useClientTable(filteredUsers)

const fetchUsers = async () => {
  loading.value = true
  try {
    const res = await $api('/users', {
      query: {
        with_roles: true,
        paginate: false,
      },
    })

    allUsers.value = res.data
  }
  catch (err) {
    showSnackbar(err?.data?.errors?.auth || 'Erreur lors du chargement des utilisateurs', 'error')
  }
  finally {
    loading.value = false
  }
}

const fetchRoles = async () => {
  try {
    const res = await $api('/roles', { query: { paginate: false } })

    roleOptions.value = res.data.map(role => ({
      title: role.label || role.name,
      value: role.id,
    }))
  }
  catch {
    roleOptions.value = []
  }
}

onMounted(() => {
  fetchUsers()
  fetchRoles()
})

// ----- Snackbar -----
const snackbar = ref({ show: false, text: '', color: 'success' })

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

// ----- Create / Edit dialog -----
const isDialogVisible = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const errors = ref({})

const emptyForm = () => ({
  id: null,
  name: '',
  email: '',
  password: '',
  activated: true,
  password_change_required: true,
  roles: [],
})

const form = ref(emptyForm())

const openCreateDialog = () => {
  isEditing.value = false
  errors.value = {}
  form.value = emptyForm()
  isDialogVisible.value = true
}

const openEditDialog = user => {
  isEditing.value = true
  errors.value = {}
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
    activated: !!user.activated,
    password_change_required: !!user.password_change_required,
    roles: (user.roles || []).map(role => role.id),
  }
  isDialogVisible.value = true
}

const fieldErrors = field => {
  const value = errors.value[field]

  return value ? String(value).split('<br>') : []
}

// ----- Champs du formulaire (pilotés par FieldRenderer) -----
const userFormFields = computed(() => [
  {
    value_key: 'name',
    type: 'text',
    label: 'Nom',
    required: true,
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'email',
    type: 'email',
    label: 'Email',
    required: true,
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'password',
    type: 'password',
    label: 'Mot de passe',
    required: !isEditing.value,
    placeholder: isEditing.value ? 'Laisser vide pour ne pas changer' : '',
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'roles',
    type: 'lov',
    label: 'Rôles',
    data: { list: { items: roleOptions.value, multiple: true, chips: true } },
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'activated',
    type: 'boolean',
    label: 'Compte activé',
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'password_change_required',
    type: 'boolean',
    label: 'Changement de mot de passe requis',
    cols: { cols: 12, md: 6 },
  },
])

const activeUserFormFields = computed(() => userFormFields.value
  .map(field => ({ ...field, errors: fieldErrors(field.value_key) })))

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  const body = {
    name: form.value.name,
    email: form.value.email,
    activated: form.value.activated,
    password_change_required: form.value.password_change_required,
    roles: form.value.roles,
  }

  if (form.value.password)
    body.password = form.value.password

  try {
    if (isEditing.value) {
      await $api(`/users/${form.value.id}`, {
        method: 'PUT',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Utilisateur mis à jour avec succès')
    }
    else {
      await $api('/users', {
        method: 'POST',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Utilisateur créé avec succès')
    }

    isDialogVisible.value = false
    await fetchUsers()
  }
  catch (err) {
    if (!Object.keys(errors.value).length)
      showSnackbar(err?.data?.errors?.auth || 'Une erreur est survenue', 'error')
  }
  finally {
    saving.value = false
  }
}

// ----- Delete -----
const isDeleteDialogVisible = ref(false)
const userToDelete = ref(null)
const deleting = ref(false)

// Ids des lignes en cours de suppression, pour jouer l'animation de disparition avant de recharger la liste
const deletingIds = reactive(new Set())
const rowProps = ({ item }) => ({ class: deletingIds.has(item.id) ? 'row-removing' : undefined })
const ROW_REMOVE_ANIMATION_DURATION = 300

const confirmDelete = user => {
  userToDelete.value = user
  isDeleteDialogVisible.value = true
}

const deleteUser = async () => {
  if (!userToDelete.value)
    return

  const id = userToDelete.value.id

  deleting.value = true
  try {
    await $api(`/users/${id}`, { method: 'DELETE' })
    showSnackbar('Utilisateur supprimé avec succès')
    isDeleteDialogVisible.value = false

    deletingIds.add(id)
    await new Promise(resolve => setTimeout(resolve, ROW_REMOVE_ANIMATION_DURATION))
    await fetchUsers()
    deletingIds.delete(id)
  }
  catch (err) {
    showSnackbar(err?.data?.errors?.auth || err?.data?.errors?.id || 'Une erreur est survenue', 'error')
  }
  finally {
    deleting.value = false
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center justify-space-between">
        <div>
          <h4 class="text-h4 mb-1">
            Utilisateurs
          </h4>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Gérez les comptes utilisateurs et leurs rôles
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-refresh"
            :loading="loading"
            :disabled="loading"
            @click="fetchUsers"
          >
            Recharger
            <template #loader>
              <span class="custom-loader">
                <VIcon icon="tabler-refresh" />
              </span>
            </template>
          </VBtn>

          <VBtn
            v-if="ability.can('create', 'user')"
            prepend-icon="tabler-plus"
            @click="openCreateDialog"
          >
            Ajouter
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <AppTextField
          v-model="search"
          placeholder="Rechercher un nom, un email..."
          prepend-inner-icon="tabler-search"
          style="min-width: 220px;"
        />

        <AppSelect
          v-model="selectedRole"
          placeholder="Filtrer par rôle"
          :items="roleOptions"
          clearable
          clear-icon="tabler-x"
          style="min-width: 200px;"
        />

        <AppSelect
          v-model="selectedStatus"
          placeholder="Filtrer par statut"
          :items="statusOptions"
          clearable
          clear-icon="tabler-x"
          style="min-width: 180px;"
        />
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        :items-per-page-options="itemsPerPageOptions"
        :headers="headers"
        :items="users"
        :items-length="totalUsers"
        :loading="loading"
        :row-props="rowProps"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar
              color="primary"
              variant="tonal"
              size="38"
            >
              <span class="text-caption font-weight-bold">{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div>
              <div class="font-weight-medium">
                {{ item.name }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.roles="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip
              v-for="role in item.roles"
              :key="role.id"
              size="small"
              :color="role.is_super_admin ? 'error' : 'primary'"
              label
            >
              {{ role.label || role.name }}
            </VChip>
            <span
              v-if="!item.roles?.length"
              class="text-disabled"
            >—</span>
          </div>
        </template>

        <template #item.activated="{ item }">
          <VChip
            size="small"
            :color="item.activated ? 'success' : 'secondary'"
            label
          >
            {{ item.activated ? 'Actif' : 'Inactif' }}
          </VChip>
        </template>

        <template #item.password_change_required="{ item }">
          <VChip
            size="small"
            :color="item.password_change_required ? 'warning' : 'secondary'"
            variant="tonal"
            label
          >
            {{ item.password_change_required ? 'Changement requis' : 'OK' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-end gap-1">
            <VBtn
              v-if="ability.can('update', 'user')"
              icon
              variant="text"
              size="small"
              @click="openEditDialog(item)"
            >
              <VIcon icon="tabler-edit" />
            </VBtn>
            <VBtn
              v-if="ability.can('delete', 'user') && item.id !== userData?.id"
              icon
              variant="text"
              size="small"
              color="error"
              @click="confirmDelete(item)"
            >
              <VIcon icon="tabler-trash" />
            </VBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Create / Edit dialog -->
    <VDialog
      v-model="isDialogVisible"
      max-width="600"
      persistent
    >
      <VCard>
        <VCardTitle class="pa-5">
          {{ isEditing ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}
        </VCardTitle>
        <VDivider />

        <VCardText class="pa-5">
          <VRow>
            <VCol
              v-for="field in activeUserFormFields"
              :key="field.value_key"
              v-bind="field.cols"
            >
              <FieldRenderer
                v-model="form[field.value_key]"
                :field="field"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDialogVisible = false"
          >
            Annuler
          </VBtn>
          <VBtn
            :loading="saving"
            @click="submitForm"
          >
            {{ isEditing ? 'Enregistrer' : 'Créer' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Delete confirm dialog -->
    <VDialog
      v-model="isDeleteDialogVisible"
      max-width="450"
    >
      <VCard>
        <VCardTitle class="pa-5">
          Supprimer l'utilisateur
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          Voulez-vous vraiment supprimer <strong>{{ userToDelete?.name }}</strong> ?
          Cette action est irréversible.
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDeleteDialogVisible = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            :loading="deleting"
            @click="deleteUser"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      location="bottom end"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </div>
</template>

<style lang="scss" scoped>
.custom-loader {
  display: flex;
  animation: loader 1s infinite;
}

@keyframes loader {
  from {
    transform: rotate(0);
  }

  to {
    transform: rotate(360deg);
  }
}

// Animation de disparition d'une ligne du tableau lors de sa suppression
:deep(.row-removing) {
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease, transform 0.3s ease;
  transform: scale(0.97);
}
</style>
