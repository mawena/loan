export default () => [
  {
    title: 'Accueil',
    icon: { icon: 'tabler-home' },
    to: 'root',
    action: 'read',
    subject: 'Auth',
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
