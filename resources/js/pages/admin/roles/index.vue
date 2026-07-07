<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'role',
  },
})

const ability = useAbility()

const headers = [
  { title: 'Rôle', key: 'name' },
  { title: 'Description', key: 'description' },
  { title: 'Permissions', key: 'permissions', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: -1, title: 'Tout' },
]

const typeOptions = [
  { title: 'Super administrateur', value: true },
  { title: 'Standard', value: false },
]

const loading = ref(false)
const allRoles = ref([])
const permissionOptions = ref([])

// ----- Filtres (frontend) -----
const search = ref('')
const selectedType = ref(null)

const filteredRoles = computed(() => allRoles.value.filter(role => {
  const matchesSearchQuery = matchesSearch(role, ['name', 'label', 'description'], search.value)
  const matchesType = selectedType.value === null || !!role.is_super_admin === selectedType.value

  return matchesSearchQuery && matchesType
}))

// ----- Tri & pagination (frontend) -----
const {
  page,
  itemsPerPage,
  updateOptions,
  paginatedItems: roles,
  totalItems: totalRoles,
} = useClientTable(filteredRoles)

const fetchRoles = async () => {
  loading.value = true
  try {
    const res = await $api('/roles', {
      query: {
        with_permissions: true,
        paginate: false,
      },
    })

    allRoles.value = res.data
  }
  catch (err) {
    showSnackbar(err?.data?.errors?.auth || 'Erreur lors du chargement des rôles', 'error')
  }
  finally {
    loading.value = false
  }
}

const fetchPermissions = async () => {
  try {
    const res = await $api('/permissions', { query: { paginate: false } })

    permissionOptions.value = res.data.map(permission => ({
      title: permission.label || `${permission.action} ${permission.subject}`,
      value: permission.id,
    }))
  }
  catch {
    permissionOptions.value = []
  }
}

onMounted(() => {
  fetchRoles()
  fetchPermissions()
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
  label: '',
  description: '',
  is_super_admin: false,
  permissions: [],
})

const form = ref(emptyForm())

const openCreateDialog = () => {
  isEditing.value = false
  errors.value = {}
  form.value = emptyForm()
  isDialogVisible.value = true
}

const openEditDialog = role => {
  isEditing.value = true
  errors.value = {}
  form.value = {
    id: role.id,
    name: role.name,
    label: role.label || '',
    description: role.description || '',
    is_super_admin: !!role.is_super_admin,
    permissions: (role.permissions || []).map(permission => permission.id),
  }
  isDialogVisible.value = true
}

const fieldErrors = field => {
  const value = errors.value[field]

  return value ? String(value).split('<br>') : []
}

// ----- Champs du formulaire (pilotés par FieldRenderer) -----
const roleFormFields = computed(() => [
  {
    value_key: 'name',
    type: 'text',
    label: 'Nom (identifiant)',
    required: true,
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'label',
    type: 'text',
    label: 'Libellé',
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'description',
    type: 'text',
    label: 'Description',
    cols: { cols: 12 },
  },
  {
    value_key: 'is_super_admin',
    type: 'boolean',
    label: 'Super administrateur (tous les droits)',
    cols: { cols: 12 },
  },
  {
    value_key: 'permissions',
    type: 'lov',
    label: 'Permissions',
    data: { list: { items: permissionOptions.value, multiple: true, chips: true } },
    cols: { cols: 12 },
    show: () => !form.value.is_super_admin,
  },
])

const activeRoleFormFields = computed(() => roleFormFields.value
  .filter(field => !field.show || field.show())
  .map(field => ({ ...field, errors: fieldErrors(field.value_key) })))

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  const body = {
    name: form.value.name,
    label: form.value.label,
    description: form.value.description,
    is_super_admin: form.value.is_super_admin,
    permissions: form.value.permissions,
  }

  try {
    if (isEditing.value) {
      await $api(`/roles/${form.value.id}`, {
        method: 'PUT',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Rôle mis à jour avec succès')
    }
    else {
      await $api('/roles', {
        method: 'POST',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Rôle créé avec succès')
    }

    isDialogVisible.value = false
    await fetchRoles()
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
const roleToDelete = ref(null)
const deleting = ref(false)

// Ids des lignes en cours de suppression, pour jouer l'animation de disparition avant de recharger la liste
const deletingIds = reactive(new Set())
const rowProps = ({ item }) => ({ class: deletingIds.has(item.id) ? 'row-removing' : undefined })
const ROW_REMOVE_ANIMATION_DURATION = 300

const confirmDelete = role => {
  roleToDelete.value = role
  isDeleteDialogVisible.value = true
}

const deleteRole = async () => {
  if (!roleToDelete.value)
    return

  const id = roleToDelete.value.id

  deleting.value = true
  try {
    await $api(`/roles/${id}`, { method: 'DELETE' })
    showSnackbar('Rôle supprimé avec succès')
    isDeleteDialogVisible.value = false

    deletingIds.add(id)
    await new Promise(resolve => setTimeout(resolve, ROW_REMOVE_ANIMATION_DURATION))
    await fetchRoles()
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
            Rôles
          </h4>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Gérez les rôles et leurs permissions
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-refresh"
            :loading="loading"
            :disabled="loading"
            @click="fetchRoles"
          >
            Recharger
            <template #loader>
              <span class="custom-loader">
                <VIcon icon="tabler-refresh" />
              </span>
            </template>
          </VBtn>

          <VBtn
            v-if="ability.can('create', 'role')"
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
          placeholder="Rechercher un rôle..."
          prepend-inner-icon="tabler-search"
          style="min-width: 220px;"
        />

        <AppSelect
          v-model="selectedType"
          placeholder="Filtrer par type"
          :items="typeOptions"
          clearable
          clear-icon="tabler-x"
          style="min-width: 200px;"
        />
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        :items-per-page-options="itemsPerPageOptions"
        :headers="headers"
        :items="roles"
        :items-length="totalRoles"
        :loading="loading"
        :row-props="rowProps"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-2 py-2">
            <div class="font-weight-medium">
              {{ item.label || item.name }}
            </div>
            <VChip
              v-if="item.is_super_admin"
              size="x-small"
              color="error"
              label
            >
              Super Admin
            </VChip>
          </div>
        </template>

        <template #item.description="{ item }">
          <span class="text-medium-emphasis">{{ item.description || '—' }}</span>
        </template>

        <template #item.permissions="{ item }">
          <div
            v-if="item.is_super_admin"
            class="text-medium-emphasis"
          >
            Toutes les permissions
          </div>
          <div
            v-else
            class="d-flex flex-wrap gap-1"
          >
            <VChip
              v-for="permission in item.permissions"
              :key="permission.id"
              size="small"
              color="primary"
              variant="tonal"
              label
            >
              {{ permission.label || `${permission.action} ${permission.subject}` }}
            </VChip>
            <span
              v-if="!item.permissions?.length"
              class="text-disabled"
            >—</span>
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-end gap-1">
            <VBtn
              v-if="ability.can('update', 'role')"
              icon
              variant="text"
              size="small"
              @click="openEditDialog(item)"
            >
              <VIcon icon="tabler-edit" />
            </VBtn>
            <VBtn
              v-if="ability.can('delete', 'role')"
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
            :total-items="totalRoles"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Create / Edit dialog -->
    <VDialog
      v-model="isDialogVisible"
      max-width="650"
      persistent
    >
      <VCard>
        <VCardTitle class="pa-5">
          {{ isEditing ? 'Modifier le rôle' : 'Ajouter un rôle' }}
        </VCardTitle>
        <VDivider />

        <VCardText class="pa-5">
          <VRow>
            <VCol
              v-for="field in activeRoleFormFields"
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
          Supprimer le rôle
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          Voulez-vous vraiment supprimer le rôle <strong>{{ roleToDelete?.label || roleToDelete?.name }}</strong> ?
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
            @click="deleteRole"
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
