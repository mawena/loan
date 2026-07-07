<script setup>
import { French } from 'flatpickr/dist/l10n/fr'

const props = defineProps({
  // Définition du champ : { value_key, type, label, required, placeholder, errors, data, rules }
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: null,
  },
  validationRules: {
    type: Array,
    default: () => [],
  },

  // Contexte optionnel transmis par l'appelant (ex: type d'un formulaire), disponible
  // pour les règles de validation propres au champ (field.rules) sans coupler ce composant
  // à une logique métier particulière.
  context: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const modelValue = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

// Règles finales : requis (si field.required) + règles globales du formulaire + règles propres au champ
const finalValidationRules = computed(() => {
  const requiredRule = props.field.required ? [requiredValidator] : []
  const fieldRules = props.field.rules ?? []

  return [...requiredRule, ...props.validationRules, ...fieldRules]
})
</script>

<template>
  <div>
    <!-- Champs texte, nombre, email, mot de passe -->
    <AppTextField
      v-if="['text', 'number', 'email', 'password'].includes(field.type)"
      v-model="modelValue"
      :type="field.type"
      :label="field.label"
      :placeholder="field.placeholder"
      :error-messages="field.errors"
      :rules="finalValidationRules"
    />

    <!-- Champ textarea -->
    <AppTextarea
      v-else-if="field.type === 'textarea'"
      v-model="modelValue"
      :label="field.label"
      :placeholder="field.placeholder"
      :error-messages="field.errors"
      :rules="finalValidationRules"
    />

    <!-- Champ Tiptap Editor (pas de support natif des erreurs/rules : affichées manuellement) -->
    <div v-else-if="field.type === 'tiptap'">
      <VLabel
        v-if="field.label"
        class="mb-1 text-body-2"
        :text="field.label"
      />
      <TiptapEditor
        v-model="modelValue"
        class="border rounded basic-editor"
        :placeholder="field.placeholder ?? field.label"
      />
      <div
        v-if="field.errors?.length"
        class="text-error text-caption mt-1"
      >
        {{ field.errors[0] }}
      </div>
    </div>

    <!-- Champ date -->
    <AppDateTimePicker
      v-else-if="field.type === 'date'"
      v-model="modelValue"
      :label="field.label"
      :error-messages="field.errors"
      :rules="finalValidationRules"
      :config="{
        dateFormat: 'd/m/Y',
        locale: French,
      }"
    />

    <!-- Champ LOV (List of Values) -->
    <AppAutocomplete
      v-else-if="field.type === 'lov'"
      v-model="modelValue"
      :label="field.label"
      :items="field.data.list.items"
      :item-title="field.data.list.name ?? 'title'"
      :item-value="field.data.list.id ?? 'value'"
      :multiple="field.data.list.multiple ?? false"
      :chips="field.data.list.chips ?? false"
      :closable-chips="field.data.list.chips ?? false"
      :error-messages="field.errors"
      :rules="finalValidationRules"
      clearable
      clear-icon="tabler-x"
    />

    <!-- Champ boolean (switch) -->
    <VSwitch
      v-else-if="field.type === 'boolean'"
      v-model="modelValue"
      :label="field.label"
      :error-messages="field.errors"
      :true-value="field.data?.boolean?.true_value ?? true"
      :false-value="field.data?.boolean?.false_value ?? false"
      :rules="finalValidationRules"
    />

    <!-- Champ checkbox -->
    <VCheckbox
      v-else-if="field.type === 'checkbox'"
      v-model="modelValue"
      :label="field.label"
      :error-messages="field.errors"
      :rules="finalValidationRules"
    />

    <!-- Champ select -->
    <AppSelect
      v-else-if="field.type === 'select'"
      v-model="modelValue"
      :items="field.data?.options ?? []"
      :label="field.label"
      :error-messages="field.errors"
      :rules="finalValidationRules"
      clearable
      clear-icon="tabler-x"
    />

    <!-- Champ fichier -->
    <VFileInput
      v-else-if="field.type === 'file'"
      v-model="modelValue"
      :label="field.label"
      :error-messages="field.errors"
      :rules="finalValidationRules"
    />
  </div>
</template>
