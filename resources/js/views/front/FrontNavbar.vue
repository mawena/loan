<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const userData = useCookie('userData')
const isMobileMenuOpen = ref(false)

const links = [
  { title: 'Simulateur', to: { path: '/simulateur' } },
  { title: 'Comparateur', to: { path: '/comparateur' } },
  { title: 'Remboursement anticipé', to: { path: '/remboursement-anticipe' } },
  { title: 'Capacité d\'emprunt', to: { path: '/capacite-emprunt' } },
  { title: 'Tarifs', to: { path: '/', hash: '#tarifs' } },
]
</script>

<template>
  <header class="front-navbar">
    <div class="front-container d-flex align-center gap-4">
      <RouterLink
        to="/"
        class="d-flex align-center gap-x-2 front-brand"
      >
        <VNodeRenderer :nodes="themeConfig.app.logo" />
        <span class="front-brand-name">{{ themeConfig.app.title }}</span>
      </RouterLink>

      <VSpacer />

      <nav class="d-none d-md-flex align-center gap-1">
        <RouterLink
          v-for="link in links"
          :key="link.title"
          :to="link.to"
          class="front-nav-link"
        >
          {{ link.title }}
        </RouterLink>
      </nav>

      <VSpacer class="d-none d-md-block" />

      <NavbarThemeSwitcher />

      <template v-if="userData">
        <VBtn
          variant="tonal"
          size="small"
          to="/mes-simulations"
          prepend-icon="tabler-history"
          class="d-none d-sm-flex"
        >
          Mes simulations
        </VBtn>
        <VAvatar
          color="primary"
          variant="tonal"
          size="34"
        >
          <span class="text-sm font-weight-medium">{{ (userData.name || '?').charAt(0).toUpperCase() }}</span>
        </VAvatar>
      </template>
      <VBtn
        v-else
        size="small"
        to="/login"
        prepend-icon="tabler-login-2"
      >
        Se connecter
      </VBtn>

      <IconBtn
        class="d-md-none"
        @click="isMobileMenuOpen = !isMobileMenuOpen"
      >
        <VIcon :icon="isMobileMenuOpen ? 'tabler-x' : 'tabler-menu-2'" />
      </IconBtn>
    </div>

    <VExpandTransition>
      <nav
        v-if="isMobileMenuOpen"
        class="front-mobile-menu d-md-none"
      >
        <RouterLink
          v-for="link in links"
          :key="link.title"
          :to="link.to"
          class="front-nav-link"
          @click="isMobileMenuOpen = false"
        >
          {{ link.title }}
        </RouterLink>
        <RouterLink
          v-if="userData"
          to="/mes-simulations"
          class="front-nav-link"
          @click="isMobileMenuOpen = false"
        >
          Mes simulations
        </RouterLink>
      </nav>
    </VExpandTransition>
  </header>
</template>

<style lang="scss">
/* stylelint-disable no-invalid-position-at-import-rule */
@import url("https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=IBM+Plex+Mono:wght@500;600&display=swap");

/* ===== Styles partagés des pages publiques (navbar montée sur chacune) ===== */
.front-container {
  margin-inline: auto;
  max-inline-size: 1200px;
  padding-inline: 1.5rem;
}

.front-display {
  font-family: "Bricolage Grotesque", "Public Sans", sans-serif;
  letter-spacing: -0.025em;
}

.front-mono {
  font-family: "IBM Plex Mono", monospace;
  font-variant-numeric: tabular-nums;
}

/* Bande textile — clin d'œil aux étoffes ouest-africaines, couleurs des billets FCFA */
.kente-band {
  background: repeating-linear-gradient(
    90deg,
    #6c4bc4 0 26px,
    #e8a33d 26px 44px,
    #17897b 44px 62px,
    #c2502c 62px 78px
  );
  block-size: 4px;
}

.front-navbar {
  position: sticky;
  z-index: 10;
  backdrop-filter: blur(12px);
  background: rgba(var(--v-theme-surface), 0.82);
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  inset-block-start: 0;
  padding-block: 0.65rem;

  .front-brand {
    text-decoration: none;
  }

  .front-brand-name {
    color: rgb(var(--v-theme-on-surface));
    font-family: "Bricolage Grotesque", "Public Sans", sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }
}

.front-nav-link {
  border-radius: 0.5rem;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.9375rem;
  font-weight: 500;
  padding-block: 0.4rem;
  padding-inline: 0.75rem;
  text-decoration: none;
  transition: background 0.18s ease, color 0.18s ease;

  &:hover {
    background: rgba(var(--v-theme-primary), 0.08);
    color: rgb(var(--v-theme-primary));
  }
}

.front-mobile-menu {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding-block: 0.75rem 0.25rem;
  padding-inline: 1.25rem;
}
</style>
