<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'permission',
  },
})

const ability = useAbility()

const headers = [
  { title: 'Action', key: 'action' },
  { title: 'Sujet', key: 'subject' },
  { title: 'Libellé', key: 'label' },
  { title: 'Description', key: 'description' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: -1, title: 'Tout' },
]

const loading = ref(false)
const allPermissions = ref([])

// ----- Filtres (frontend) -----
const search = ref('')
const selectedAction = ref(null)
const selectedSubject = ref(null)

// Options déduites des données chargées (pas d'appel réseau dédié)
const actionOptions = computed(() => [...new Set(allPermissions.value.map(permission => permission.action))]
  .sort()
  .map(action => ({ title: action, value: action })))

const subjectOptions = computed(() => [...new Set(allPermissions.value.map(permission => permission.subject))]
  .sort()
  .map(subject => ({ title: subject, value: subject })))

const filteredPermissions = computed(() => allPermissions.value.filter(permission => {
  const matchesSearchQuery = matchesSearch(permission, ['action', 'subject', 'label', 'description'], search.value)
  const matchesAction = !selectedAction.value || permission.action === selectedAction.value
  const matchesSubject = !selectedSubject.value || permission.subject === selectedSubject.value

  return matchesSearchQuery && matchesAction && matchesSubject
}))

// ----- Tri & pagination (frontend) -----
const {
  page,
  itemsPerPage,
  updateOptions,
  paginatedItems: permissions,
  totalItems: totalPermissions,
} = useClientTable(filteredPermissions)

const fetchPermissions = async () => {
  loading.value = true
  try {
    const res = await $api('/permissions', {
      query: {
        paginate: false,
      },
    })

    allPermissions.value = res.data
  }
  catch (err) {
    showSnackbar(err?.data?.errors?.auth || 'Erreur lors du chargement des permissions', 'error')
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
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
  action: '',
  subject: '',
  label: '',
  description: '',
})

const form = ref(emptyForm())

const openCreateDialog = () => {
  isEditing.value = false
  errors.value = {}
  form.value = emptyForm()
  isDialogVisible.value = true
}

const openEditDialog = permission => {
  isEditing.value = true
  errors.value = {}
  form.value = {
    id: permission.id,
    action: permission.action,
    subject: permission.subject,
    label: permission.label || '',
    description: permission.description || '',
  }
  isDialogVisible.value = true
}

const fieldErrors = field => {
  const value = errors.value[field]

  return value ? String(value).split('<br>') : []
}

// ----- Champs du formulaire (pilotés par FieldRenderer) -----
const permissionFormFields = computed(() => [
  {
    value_key: 'action',
    type: 'text',
    label: 'Action',
    placeholder: 'ex: create, read, update, delete',
    required: true,
    cols: { cols: 12, md: 6 },
  },
  {
    value_key: 'subject',
    type: 'text',
    label: 'Sujet',
    placeholder: 'ex: user, role, permission',
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
    cols: { cols: 12, md: 6 },
  },
])

const activePermissionFormFields = computed(() => permissionFormFields.value
  .map(field => ({ ...field, errors: fieldErrors(field.value_key) })))

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  const body = {
    action: form.value.action,
    subject: form.value.subject,
    label: form.value.label,
    description: form.value.description,
  }

  try {
    if (isEditing.value) {
      await $api(`/permissions/${form.value.id}`, {
        method: 'PUT',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Permission mise à jour avec succès')
    }
    else {
      await $api('/permissions', {
        method: 'POST',
        body,
        onResponseError({ response }) {
          errors.value = response._data.errors || {}
        },
      })
      showSnackbar('Permission créée avec succès')
    }

    isDialogVisible.value = false
    await fetchPermissions()
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
const permissionToDelete = ref(null)
const deleting = ref(false)

// Ids des lignes en cours de suppression, pour jouer l'animation de disparition avant de recharger la liste
const deletingIds = reactive(new Set())
const rowProps = ({ item }) => ({ class: deletingIds.has(item.id) ? 'row-removing' : undefined })
const ROW_REMOVE_ANIMATION_DURATION = 300

const confirmDelete = permission => {
  permissionToDelete.value = permission
  isDeleteDialogVisible.value = true
}

const deletePermission = async () => {
  if (!permissionToDelete.value)
    return

  const id = permissionToDelete.value.id

  deleting.value = true
  try {
    await $api(`/permissions/${id}`, { method: 'DELETE' })
    showSnackbar('Permission supprimée avec succès')
    isDeleteDialogVisible.value = false

    deletingIds.add(id)
    await new Promise(resolve => setTimeout(resolve, ROW_REMOVE_ANIMATION_DURATION))
    await fetchPermissions()
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
            Permissions
          </h4>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Gérez les permissions disponibles pour les rôles
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-refresh"
            :loading="loading"
            :disabled="loading"
            @click="fetchPermissions"
          >
            Recharger
            <template #loader>
              <span class="custom-loader">
                <VIcon icon="tabler-refresh" />
              </span>
            </template>
          </VBtn>

          <VBtn
            v-if="ability.can('create', 'permission')"
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
          placeholder="Rechercher une permission..."
          prepend-inner-icon="tabler-search"
          style="min-width: 220px;"
        />

        <AppSelect
          v-model="selectedAction"
          placeholder="Filtrer par action"
          :items="actionOptions"
          clearable
          clear-icon="tabler-x"
          style="min-width: 180px;"
        />

        <AppSelect
          v-model="selectedSubject"
          placeholder="Filtrer par sujet"
          :items="subjectOptions"
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
        :items="permissions"
        :items-length="totalPermissions"
        :loading="loading"
        :row-props="rowProps"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.action="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="tonal"
            label
          >
            {{ item.action }}
          </VChip>
        </template>

        <template #item.subject="{ item }">
          <VChip
            size="small"
            color="secondary"
            variant="tonal"
            label
          >
            {{ item.subject }}
          </VChip>
        </template>

        <template #item.label="{ item }">
          {{ item.label || '—' }}
        </template>

        <template #item.description="{ item }">
          <span class="text-medium-emphasis">{{ item.description || '—' }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-end gap-1">
            <VBtn
              v-if="ability.can('update', 'permission')"
              icon
              variant="text"
              size="small"
              @click="openEditDialog(item)"
            >
              <VIcon icon="tabler-edit" />
            </VBtn>
            <VBtn
              v-if="ability.can('delete', 'permission')"
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
            :total-items="totalPermissions"
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
          {{ isEditing ? 'Modifier la permission' : 'Ajouter une permission' }}
        </VCardTitle>
        <VDivider />

        <VCardText class="pa-5">
          <VRow>
            <VCol
              v-for="field in activePermissionFormFields"
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
          Supprimer la permission
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          Voulez-vous vraiment supprimer la permission
          <strong>{{ permissionToDelete?.label || `${permissionToDelete?.action} ${permissionToDelete?.subject}` }}</strong> ?
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
            @click="deletePermission"
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
