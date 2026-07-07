<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const year = new Date().getFullYear()
</script>

<template>
  <footer class="front-footer">
    <div class="kente-band" />
    <div class="front-container">
      <div class="d-flex flex-wrap justify-space-between align-start gap-6 py-10">
        <div style="max-inline-size: 320px;">
          <div class="d-flex align-center gap-x-2 mb-3">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
            <span class="front-brand-name">{{ themeConfig.app.title }}</span>
          </div>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Simulez, comparez et téléchargez vos tableaux d'amortissement en FCFA,
            avant de rencontrer votre banquier.
          </p>
        </div>

        <div class="d-flex gap-12 flex-wrap">
          <div>
            <h6 class="text-h6 mb-3">
              Outils
            </h6>
            <ul class="front-footer-links">
              <li>
                <RouterLink to="/simulateur">
                  Tableau d'amortissement
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/comparateur">
                  Comparateur de prêts
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/remboursement-anticipe">
                  Remboursement anticipé
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/capacite-emprunt">
                  Capacité d'emprunt
                </RouterLink>
              </li>
            </ul>
          </div>
          <div>
            <h6 class="text-h6 mb-3">
              Compte
            </h6>
            <ul class="front-footer-links">
              <li>
                <RouterLink to="/login">
                  Se connecter
                </RouterLink>
              </li>
              <li>
                <RouterLink to="/mes-simulations">
                  Mes simulations
                </RouterLink>
              </li>
              <li>
                <RouterLink :to="{ path: '/', hash: '#tarifs' }">
                  Tarifs
                </RouterLink>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <VDivider />

      <div class="d-flex flex-wrap justify-space-between align-center gap-2 py-4">
        <span class="text-body-2 text-medium-emphasis">© {{ year }} {{ themeConfig.app.title }} — Tous droits réservés.</span>
        <span class="text-caption text-disabled">
          Les résultats sont fournis à titre indicatif et ne constituent pas une offre de crédit.
        </span>
      </div>
    </div>
  </footer>
</template>

<style lang="scss">
.front-footer {
  background: rgb(var(--v-theme-surface));

  .front-footer-links {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    list-style: none;
    padding-inline-start: 0;

    a {
      color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
      font-size: 0.9375rem;
      text-decoration: none;
      transition: color 0.18s ease;

      &:hover {
        color: rgb(var(--v-theme-primary));
      }
    }
  }
}
</style>
