import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // 前台布局
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: [
      {
        path: '',
        name: 'Home',
        component: () => import('@/pages/Home.vue'),
      },
      {
        path: 'software',
        name: 'SoftwareList',
        component: () => import('@/pages/SoftwareList.vue'),
      },
      {
        path: 'software/:id',
        name: 'SoftwareDetail',
        component: () => import('@/pages/SoftwareDetail.vue'),
      },
      {
        path: 'search',
        name: 'Search',
        component: () => import('@/pages/Search.vue'),
      },
      {
        path: 'recharge',
        name: 'Recharge',
        component: () => import('@/pages/Recharge.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'report',
        name: 'Report',
        component: () => import('@/pages/Report.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  // 登录/注册（无布局）
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/pages/Login.vue'),
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/pages/Register.vue'),
  },
  // 用户中心布局
  {
    path: '/user',
    component: () => import('@/layouts/UserLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: { name: 'UserCenter' },
      },
      {
        path: 'center',
        name: 'UserCenter',
        component: () => import('@/pages/UserCenter.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'profile',
        name: 'UserProfile',
        component: () => import('@/pages/UserProfile.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'points',
        name: 'UserPoints',
        component: () => import('@/pages/UserPoints.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'favorites',
        name: 'UserFavorites',
        component: () => import('@/pages/UserFavorites.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'downloads',
        name: 'UserDownloads',
        component: () => import('@/pages/UserDownloads.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'recharge',
        name: 'UserRecharge',
        component: () => import('@/pages/UserRecharge.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
})

export default router
