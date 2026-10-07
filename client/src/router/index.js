import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/games',
    name: 'games',
    component: () => import('../views/AllGamesView.vue')
  },
  {
    path: '/wallet',
    name: 'wallet',
    component: () => import('../views/WalletView.vue')
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/ProfileView.vue')
  },
  {
    path: '/activity',
    name: 'activity',
    component: () => import('../views/ActivityView.vue')
  },
  {
    path: '/rankings',
    name: 'rankings',
    component: () => import('../views/RankingsView.vue')
  },
  {
      path: '/games/chicken',
      name: 'chicken',
      component: () => import('../views/ChickenGameView.vue')
    },
    {
      path: '/games/dino-cross',
      name: 'dino-cross',
      component: () => import('../views/DinoCrossView.vue')
    },
  {
    path: '/game/chicken-fight',
    name: 'chicken-fight',
    component: () => import('../views/ChickenFightView.vue')
  },
  {
    path: '/game/aviator',
    name: 'aviator',
    component: () => import('../views/AviatorView.vue')
  },
  {
    path: '/game/tom-jerry',
    name: 'tom-jerry',
    component: () => import('../views/TomJerryView.vue')
  },
  {
    path: '/game/three-patti',
    name: 'three-patti',
    component: () => import('../views/ThreePattiView.vue')
  },
  {
    path: '/games/:slug',
    name: 'game-profile',
    component: () => import('../views/GameProfileView.vue')
  },
  {
    path: '/game/:slug',
    name: 'game',
    component: () => import('../views/GameView.vue')
  },
  {
    path: '/ludo',
    name: 'ludo',
    component: () => import('../views/LudoView.vue')
  },
  {
    path: '/cricket',
    name: 'cricket',
    component: () => import('../views/CricketView.vue')
  },
  {
    path: '/cricket3d',
    name: 'cricket3d',
    component: () => import('../views/Cricket3DView.vue')
  },
  {
    path: '/carrom',
    name: 'carrom',
    component: () => import('../views/CarromView.vue')
  },
  {
    path: '/pool/:slug',
    name: 'pool',
    component: () => import('../views/PoolView.vue')
  },
  {
    path: '/profile/bind',
    name: 'profile-bind',
    component: () => import('../views/BindAccountView.vue')
  },
  {
    path: '/profile/settings',
    name: 'profile-settings',
    component: () => import('../views/ProfileSettingsView.vue')
  },
  {
    path: '/profile/edit',
    name: 'profile-edit',
    component: () => import('../views/EditProfileView.vue')
  },
  {
    path: '/teams',
    name: 'teams',
    component: () => import('../views/TeamsView.vue')
  },
  {
    path: '/super-agent',
    component: () => import('../views/SuperAgent/Layout.vue'),
    children: [
      {
        path: 'dashboard',
        name: 'super-agent-dashboard',
        component: () => import('../views/SuperAgent/DashboardView.vue')
      },
      {
        path: 'team',
        name: 'super-agent-team',
        component: () => import('../views/SuperAgent/TeamView.vue')
      },
      {
        path: 'earnings',
        name: 'super-agent-earnings',
        component: () => import('../views/SuperAgent/EarningsView.vue')
      },
      {
        path: 'content',
        name: 'super-agent-content',
        component: () => import('../views/SuperAgent/ContentView.vue')
      },
      {
        path: 'challenges',
        name: 'super-agent-challenges',
        component: () => import('../views/SuperAgent/ChallengesView.vue')
      },
      {
        path: 'leaderboard',
        name: 'super-agent-leaderboard',
        component: () => import('../views/SuperAgent/LeaderboardView.vue')
      },
      {
        path: 'apply',
        name: 'super-agent-apply',
        component: () => import('../views/SuperAgent/ApplyView.vue')
      }
    ]
  },
  {
    path: '/tournaments',
    name: 'tournaments',
    component: () => import('../views/TournamentView.vue')
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const publicPages = ['/login', '/register']
  const authRequired = !publicPages.includes(to.path)

  if (authRequired && !token) {
    return next('/login')
  }

  next()
})

export default router
