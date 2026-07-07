<script setup>
import FrontFooter from '@/views/front/FrontFooter.vue'
import FrontNavbar from '@/views/front/FrontNavbar.vue'
import {
  firstMonthlyPayment,
  formatDuration,
  formatXOF,
  LOAN_METHODS,
  totalInterest,
} from '@/utils/loan'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const router = useRouter()

// 👉 Mini-simulateur du hero — aperçu 100 % client, ne consomme pas le quota
const amount = ref(5000000)
const rate = ref(8)
const months = ref(60)
const method = ref('annuity')

const payment = computed(() => firstMonthlyPayment(amount.value, rate.value, months.value, method.value))
const interests = computed(() => totalInterest(amount.value, rate.value, months.value, method.value))

const interestShare = computed(() => {
  const total = amount.value + interests.value

  return total > 0 ? Math.min(100, interests.value / total * 100) : 0
})

const paymentCaption = computed(() => ({
  annuity: 'Mensualité constante, du premier au dernier mois',
  constant_capital: 'Première mensualité — elle diminue ensuite chaque mois',
  in_fine: 'Intérêts mensuels — capital remboursé en une fois à la fin',
})[method.value])

const openFullSimulation = () => {
  router.push({
    path: '/simulateur',
    query: {
      amount: amount.value,
      rate: rate.value,
      months: months.value,
      method: method.value,
    },
  })
}

// 👉 Contenu des sections
const features = [
  {
    icon: 'tabler-table',
    title: "Tableau d'amortissement détaillé",
    text: 'Chaque échéance mois par mois : capital remboursé, intérêts, assurance et solde restant dû.',
  },
  {
    icon: 'tabler-git-compare',
    title: 'Comparateur de prêts',
    text: 'Jusqu\'à trois offres côte à côte — taux, durée, coût total — pour choisir la moins chère.',
  },
  {
    icon: 'tabler-coins',
    title: 'Remboursement anticipé',
    text: 'Mesurez l\'impact d\'un remboursement partiel ou total : intérêts économisés, durée ou mensualité réduite.',
  },
  {
    icon: 'tabler-wallet',
    title: "Capacité d'emprunt",
    text: 'Combien emprunter selon vos revenus et charges, avec un taux d\'endettement plafonné à 40 %.',
  },
  {
    icon: 'tabler-file-download',
    title: 'Exports PDF, Word & Excel',
    text: 'Téléchargez vos tableaux au format attendu par votre banquier, prêts à imprimer.',
  },
  {
    icon: 'tabler-history',
    title: 'Historique & partage',
    text: 'Retrouvez toutes vos simulations dans votre espace et partagez-les par un simple lien.',
  },
]

const steps = [
  {
    title: 'Décrivez votre prêt',
    text: 'Montant, taux, durée, méthode d\'amortissement et assurance éventuelle — 30 secondes suffisent.',
  },
  {
    title: 'Analysez le résultat',
    text: 'Mensualités, coût total du crédit, graphiques et échéancier complet, mois par mois.',
  },
  {
    title: 'Téléchargez ou partagez',
    text: 'Exportez en PDF, Word ou Excel, ou envoyez un lien de consultation à votre banquier.',
  },
]

const plans = [
  {
    name: 'Visiteur',
    price: 0,
    tagline: 'Pour essayer sans engagement',
    features: ['2 simulations par jour', 'Les 3 méthodes d\'amortissement', 'Tableau d\'amortissement complet'],
    cta: { label: 'Simuler maintenant', to: '/simulateur', variant: 'outlined' },
  },
  {
    name: 'Compte gratuit',
    price: 0,
    tagline: 'Le meilleur point de départ',
    highlighted: true,
    features: ['5 simulations par jour', 'Historique de vos simulations', 'Exports PDF, Word & Excel', 'Partage par lien'],
    cta: { label: 'Créer un compte', to: '/login', variant: 'flat' },
  },
  {
    name: 'Premium',
    price: 1500,
    tagline: 'Pour les professionnels du crédit',
    soon: true,
    features: ['Simulations illimitées', 'Tous les avantages du compte gratuit', 'Support prioritaire'],
    cta: { label: 'Bientôt disponible', variant: 'outlined' },
  },
]

const faqs = [
  {
    q: 'Les résultats sont-ils fiables ?',
    a: 'Nous appliquons les formules financières standard utilisées par les banques (annuités constantes, amortissement constant, in fine). Les résultats restent indicatifs : seule l\'offre écrite de votre établissement fait foi.',
  },
  {
    q: 'Quelle différence entre les trois méthodes d\'amortissement ?',
    a: 'Annuités constantes : vous payez la même mensualité chaque mois — c\'est la formule la plus courante. Amortissement constant : vous remboursez la même part de capital chaque mois, les mensualités diminuent donc avec le temps. In fine : vous ne payez que les intérêts, et le capital est remboursé en une seule fois à la fin du prêt.',
  },
  {
    q: 'Combien de simulations puis-je faire gratuitement ?',
    a: 'Sans compte, 2 simulations par jour. Avec un compte gratuit, 5 par jour, plus l\'historique et les exports. L\'offre Premium à 1 500 FCFA/mois lèvera toute limite (bientôt disponible).',
  },
  {
    q: 'Mes simulations sont-elles conservées ?',
    a: 'Si vous êtes connecté, chaque simulation est enregistrée dans votre espace personnel : vous pouvez la revoir, la re-télécharger ou la supprimer à tout moment. Elle reste privée tant que vous ne générez pas de lien de partage.',
  },
  {
    q: 'Dans quels formats puis-je télécharger mon tableau ?',
    a: 'PDF pour l\'impression, Word pour l\'éditer, Excel pour refaire vos propres calculs. Les exports sont réservés aux comptes connectés.',
  },
]
</script>

<template>
  <div class="front-page">
    <FrontNavbar />

    <!-- 👉 Hero : le simulateur est la vitrine -->
    <section class="front-hero">
      <div class="front-container">
        <VRow
          align="center"
          class="py-8 py-md-14"
        >
          <VCol
            cols="12"
            md="6"
            class="pe-md-10"
          >
            <p class="front-eyebrow mb-4">
              Simulation de crédit en FCFA
            </p>
            <h1 class="front-display front-hero-title mb-4">
              Votre prêt,<br>
              <span class="front-hero-accent">en toute clarté.</span>
            </h1>
            <p class="text-body-1 text-medium-emphasis mb-6 front-hero-sub">
              Tableau d'amortissement complet, comparaison d'offres et capacité d'emprunt —
              avant même de pousser la porte de votre banque.
            </p>

            <div class="d-flex flex-wrap gap-4 mb-8">
              <VBtn
                size="large"
                append-icon="tabler-arrow-right"
                to="/simulateur"
              >
                Simulation complète
              </VBtn>
              <VBtn
                size="large"
                variant="outlined"
                :to="{ path: '/', hash: '#fonctionnalites' }"
              >
                Découvrir les outils
              </VBtn>
            </div>

            <div class="d-flex flex-wrap gap-x-6 gap-y-2">
              <div class="d-flex align-center gap-x-2 text-body-2 text-medium-emphasis">
                <VIcon
                  icon="tabler-bolt"
                  size="18"
                  color="primary"
                /> Gratuit, sans carte bancaire
              </div>
              <div class="d-flex align-center gap-x-2 text-body-2 text-medium-emphasis">
                <VIcon
                  icon="tabler-calculator"
                  size="18"
                  color="primary"
                /> 3 méthodes d'amortissement
              </div>
              <div class="d-flex align-center gap-x-2 text-body-2 text-medium-emphasis">
                <VIcon
                  icon="tabler-file-download"
                  size="18"
                  color="primary"
                /> Export PDF, Word & Excel
              </div>
            </div>
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <!-- 👉 Carte simulateur (signature) -->
            <VCard
              class="front-simulator-card"
              elevation="10"
            >
              <div class="kente-band" />
              <VCardText class="pa-6">
                <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-6">
                  <h2 class="text-h5 front-display mb-0">
                    Estimez votre mensualité
                  </h2>
                  <VChip
                    size="small"
                    color="success"
                    variant="tonal"
                  >
                    Aperçu instantané
                  </VChip>
                </div>

                <div class="mb-5">
                  <div class="d-flex justify-space-between align-baseline mb-1">
                    <span class="text-body-2 font-weight-medium">Montant du prêt</span>
                    <span class="front-mono text-body-1 font-weight-bold">{{ formatXOF(amount) }}</span>
                  </div>
                  <VSlider
                    v-model="amount"
                    :min="250000"
                    :max="100000000"
                    :step="250000"
                    hide-details
                  />
                </div>

                <div class="mb-5">
                  <div class="d-flex justify-space-between align-baseline mb-1">
                    <span class="text-body-2 font-weight-medium">Taux annuel</span>
                    <span class="front-mono text-body-1 font-weight-bold">{{ rate.toLocaleString('fr-FR') }} %</span>
                  </div>
                  <VSlider
                    v-model="rate"
                    :min="0"
                    :max="30"
                    :step="0.25"
                    hide-details
                  />
                </div>

                <div class="mb-6">
                  <div class="d-flex justify-space-between align-baseline mb-1">
                    <span class="text-body-2 font-weight-medium">Durée</span>
                    <span class="front-mono text-body-1 font-weight-bold">{{ formatDuration(months) }}</span>
                  </div>
                  <VSlider
                    v-model="months"
                    :min="6"
                    :max="360"
                    :step="6"
                    hide-details
                  />
                </div>

                <VBtnToggle
                  v-model="method"
                  mandatory
                  divided
                  variant="outlined"
                  density="comfortable"
                  class="front-method-toggle mb-6 d-flex"
                >
                  <VBtn
                    v-for="m in LOAN_METHODS"
                    :key="m.value"
                    :value="m.value"
                    size="small"
                    class="flex-grow-1"
                  >
                    {{ m.short }}
                  </VBtn>
                </VBtnToggle>

                <div class="front-result-box pa-4 mb-4">
                  <span class="text-body-2 text-medium-emphasis d-block mb-1">Mensualité estimée</span>
                  <div class="front-mono front-result-amount">
                    {{ formatXOF(payment) }}
                  </div>
                  <span class="text-caption text-medium-emphasis">{{ paymentCaption }}</span>

                  <div class="front-breakdown mt-4">
                    <div
                      class="front-breakdown-interest"
                      :style="`inline-size: ${interestShare}%`"
                    />
                  </div>
                  <div class="d-flex justify-space-between flex-wrap mt-2">
                    <span class="text-caption">
                      <span class="front-dot front-dot-capital" /> Capital · {{ formatXOF(amount) }}
                    </span>
                    <span class="text-caption">
                      <span class="front-dot front-dot-interest" /> Intérêts · {{ formatXOF(interests) }}
                    </span>
                  </div>
                </div>

                <VBtn
                  block
                  size="large"
                  append-icon="tabler-table"
                  @click="openFullSimulation"
                >
                  Voir le tableau d'amortissement
                </VBtn>
                <p class="text-caption text-medium-emphasis text-center mt-2 mb-0">
                  L'aperçu ne compte pas dans votre quota de simulations.
                </p>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>
    </section>

    <!-- 👉 Fonctionnalités -->
    <section
      id="fonctionnalites"
      class="front-section"
    >
      <div class="front-container">
        <p class="front-eyebrow text-center mb-3">
          Fonctionnalités
        </p>
        <h2 class="front-display front-section-title text-center mb-2">
          Tout ce qu'il faut pour décider
        </h2>
        <p class="text-body-1 text-medium-emphasis text-center mx-auto mb-10 front-section-sub">
          Des outils pensés pour les emprunteurs : clairs pour vous, crédibles pour votre banquier.
        </p>

        <VRow>
          <VCol
            v-for="feature in features"
            :key="feature.title"
            cols="12"
            sm="6"
            md="4"
          >
            <VCard
              class="front-feature-card h-100"
              variant="flat"
            >
              <VCardText class="pa-6">
                <VAvatar
                  color="primary"
                  variant="tonal"
                  rounded
                  size="46"
                  class="mb-4"
                >
                  <VIcon
                    :icon="feature.icon"
                    size="26"
                  />
                </VAvatar>
                <h3 class="text-h6 mb-2">
                  {{ feature.title }}
                </h3>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  {{ feature.text }}
                </p>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>
    </section>

    <!-- 👉 Comment ça marche -->
    <section class="front-section front-section-alt">
      <div class="front-container">
        <p class="front-eyebrow text-center mb-3">
          Comment ça marche
        </p>
        <h2 class="front-display front-section-title text-center mb-10">
          Trois étapes, un dossier solide
        </h2>

        <VRow>
          <VCol
            v-for="(step, index) in steps"
            :key="step.title"
            cols="12"
            md="4"
          >
            <div class="text-center px-4">
              <div class="front-step-number front-mono mx-auto mb-4">
                {{ index + 1 }}
              </div>
              <h3 class="text-h6 mb-2">
                {{ step.title }}
              </h3>
              <p class="text-body-2 text-medium-emphasis">
                {{ step.text }}
              </p>
            </div>
          </VCol>
        </VRow>
      </div>
    </section>

    <!-- 👉 Tarifs -->
    <section
      id="tarifs"
      class="front-section"
    >
      <div class="front-container">
        <p class="front-eyebrow text-center mb-3">
          Tarifs
        </p>
        <h2 class="front-display front-section-title text-center mb-2">
          Commencez gratuitement
        </h2>
        <p class="text-body-1 text-medium-emphasis text-center mb-10">
          Aucun paiement requis pour simuler. Le Premium arrive bientôt.
        </p>

        <VRow justify="center">
          <VCol
            v-for="plan in plans"
            :key="plan.name"
            cols="12"
            sm="6"
            md="4"
          >
            <VCard
              class="front-plan-card h-100"
              :class="{ 'front-plan-highlighted': plan.highlighted }"
              variant="flat"
            >
              <div
                v-if="plan.highlighted"
                class="kente-band"
              />
              <VCardText class="pa-6 d-flex flex-column h-100">
                <div class="d-flex justify-space-between align-center mb-1">
                  <h3 class="text-h5 front-display">
                    {{ plan.name }}
                  </h3>
                  <VChip
                    v-if="plan.soon"
                    size="small"
                    color="warning"
                    variant="tonal"
                  >
                    Bientôt
                  </VChip>
                  <VChip
                    v-else-if="plan.highlighted"
                    size="small"
                    color="primary"
                    variant="tonal"
                  >
                    Recommandé
                  </VChip>
                </div>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  {{ plan.tagline }}
                </p>

                <div class="mb-5">
                  <span class="front-mono front-plan-price">{{ formatXOF(plan.price) }}</span>
                  <span class="text-body-2 text-medium-emphasis"> / mois</span>
                </div>

                <ul class="front-plan-features mb-6">
                  <li
                    v-for="item in plan.features"
                    :key="item"
                  >
                    <VIcon
                      icon="tabler-check"
                      size="18"
                      color="success"
                      class="me-2"
                    />{{ item }}
                  </li>
                </ul>

                <VBtn
                  class="mt-auto"
                  block
                  :variant="plan.cta.variant"
                  :to="plan.cta.to"
                  :disabled="plan.soon"
                >
                  {{ plan.cta.label }}
                </VBtn>
                <p
                  v-if="plan.soon"
                  class="text-caption text-medium-emphasis text-center mt-2 mb-0"
                >
                  Le paiement en ligne sera bientôt ouvert.
                </p>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>
    </section>

    <!-- 👉 FAQ -->
    <section
      id="faq"
      class="front-section front-section-alt"
    >
      <div
        class="front-container"
        style="max-inline-size: 860px;"
      >
        <p class="front-eyebrow text-center mb-3">
          FAQ
        </p>
        <h2 class="front-display front-section-title text-center mb-10">
          Questions fréquentes
        </h2>

        <VExpansionPanels variant="accordion">
          <VExpansionPanel
            v-for="faq in faqs"
            :key="faq.q"
          >
            <VExpansionPanelTitle class="text-body-1 font-weight-medium">
              {{ faq.q }}
            </VExpansionPanelTitle>
            <VExpansionPanelText class="text-body-2 text-medium-emphasis">
              {{ faq.a }}
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </div>
    </section>

    <!-- 👉 CTA final -->
    <section class="front-section">
      <div class="front-container">
        <VCard
          class="front-cta-card text-center"
          variant="flat"
        >
          <VCardText class="py-12 px-6">
            <h2 class="front-display front-section-title mb-3">
              Prêt à y voir clair ?
            </h2>
            <p class="text-body-1 text-medium-emphasis mb-6">
              Votre première simulation prend moins d'une minute — sans inscription.
            </p>
            <VBtn
              size="large"
              append-icon="tabler-arrow-right"
              to="/simulateur"
            >
              Lancer une simulation
            </VBtn>
          </VCardText>
        </VCard>
      </div>
    </section>

    <FrontFooter />
  </div>
</template>

<style lang="scss">
.front-page {
  background: rgb(var(--v-theme-background));

  .front-eyebrow {
    color: rgb(var(--v-theme-primary));
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  .front-hero {
    background:
      radial-gradient(ellipse 60% 50% at 85% 10%, rgba(115, 103, 240, 14%), transparent),
      radial-gradient(ellipse 50% 40% at 10% 90%, rgba(232, 163, 61, 10%), transparent);
  }

  .front-hero-title {
    font-size: clamp(2.4rem, 5vw, 3.6rem);
    font-weight: 800;
    line-height: 1.08;
  }

  .front-hero-accent {
    background: linear-gradient(92deg, rgb(var(--v-theme-primary)), #e8a33d);
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .front-hero-sub {
    max-inline-size: 460px;
  }

  .front-simulator-card {
    animation: front-rise 0.6s ease both;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    overflow: hidden;
  }

  .front-method-toggle {
    inline-size: 100%;
  }

  .front-result-box {
    background: rgba(var(--v-theme-primary), 0.06);
    border: 1px solid rgba(var(--v-theme-primary), 0.14);
    border-radius: 0.75rem;
  }

  .front-result-amount {
    color: rgb(var(--v-theme-primary));
    font-size: clamp(1.6rem, 3vw, 2.1rem);
    font-weight: 600;
    line-height: 1.2;
  }

  .front-breakdown {
    background: rgba(var(--v-theme-primary), 0.85);
    block-size: 10px;
    border-radius: 999px;
    overflow: hidden;
  }

  .front-breakdown-interest {
    background: #e8a33d;
    block-size: 100%;
    border-end-end-radius: 999px;
    border-start-end-radius: 999px;
    float: inline-end;
    transition: inline-size 0.35s ease;
  }

  .front-dot {
    block-size: 8px;
    border-radius: 50%;
    display: inline-block;
    inline-size: 8px;
    margin-inline-end: 2px;

    &.front-dot-capital {
      background: rgba(var(--v-theme-primary), 0.85);
    }

    &.front-dot-interest {
      background: #e8a33d;
    }
  }

  .front-section {
    padding-block: 4.5rem;
  }

  .front-section-alt {
    background: rgba(var(--v-theme-on-surface), 0.024);
  }

  .front-section-title {
    font-size: clamp(1.7rem, 3.4vw, 2.4rem);
    font-weight: 700;
  }

  .front-section-sub {
    max-inline-size: 560px;
  }

  .front-feature-card {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
      box-shadow: 0 12px 32px rgba(var(--v-theme-on-surface), 8%);
      transform: translateY(-4px);
    }
  }

  .front-step-number {
    align-items: center;
    background: rgba(var(--v-theme-primary), 0.1);
    block-size: 56px;
    border-radius: 50%;
    color: rgb(var(--v-theme-primary));
    display: flex;
    font-size: 1.4rem;
    font-weight: 600;
    inline-size: 56px;
    justify-content: center;
  }

  .front-plan-card {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    overflow: hidden;
  }

  .front-plan-highlighted {
    border-color: rgba(var(--v-theme-primary), 0.5);
    box-shadow: 0 16px 40px rgba(var(--v-theme-primary), 14%);
  }

  .front-plan-price {
    font-size: 2rem;
    font-weight: 600;
  }

  .front-plan-features {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    list-style: none;
    padding-inline-start: 0;

    li {
      align-items: center;
      display: flex;
      font-size: 0.9375rem;
    }
  }

  .front-cta-card {
    background:
      radial-gradient(ellipse 70% 90% at 50% 0%, rgba(115, 103, 240, 12%), transparent),
      rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

@keyframes front-rise {
  from {
    opacity: 0;
    transform: translateY(18px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .front-page .front-simulator-card {
    animation: none;
  }

  .front-page .front-feature-card,
  .front-page .front-breakdown-interest {
    transition: none;
  }
}
</style>
