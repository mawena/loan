export default () => [
  {
    title: 'Accueil',
    icon: { icon: 'tabler-home' },
    to: 'root',
    action: 'read',
    subject: 'Auth',
  },
  {
    title: 'Simulation',
    icon: { icon: 'tabler-calculator' },
    children: [
      {
        title: 'Simulateur de prêt',
        icon: { icon: 'tabler-calculator' },
        to: 'simulateur',
        action: 'read',
        subject: 'Auth',
      },
      {
        title: 'Mes simulations',
        icon: { icon: 'tabler-history' },
        to: 'mes-simulations',
        action: 'read',
        subject: 'simulation',
      },
      {
        title: 'Comparateur',
        icon: { icon: 'tabler-git-compare' },
        to: 'comparateur',
        action: 'read',
        subject: 'Auth',
      },
      {
        title: 'Remboursement anticipé',
        icon: { icon: 'tabler-coins' },
        to: 'remboursement-anticipe',
        action: 'read',
        subject: 'Auth',
      },
      {
        title: 'Capacité d\'emprunt',
        icon: { icon: 'tabler-wallet' },
        to: 'capacite-emprunt',
        action: 'read',
        subject: 'Auth',
      },
    ],
  },
  {
    title: 'Administration',
    icon: { icon: 'tabler-settings' },
    children: [
      {
        title: 'Utilisateurs',
        icon: { icon: 'tabler-users' },
        to: 'admin-users',
        action: 'read',
        subject: 'user',
      },
      {
        title: 'Rôles',
        icon: { icon: 'tabler-shield-lock' },
        to: 'admin-roles',
        action: 'read',
        subject: 'role',
      },
      {
        title: 'Permissions',
        icon: { icon: 'tabler-key' },
        to: 'admin-permissions',
        action: 'read',
        subject: 'permission',
      },
    ],
  },
]
