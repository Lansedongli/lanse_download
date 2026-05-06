import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { title: '登录' },
  },
  {
    path: '/',
    component: () => import('@/layout/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/Index.vue'), meta: { title: '控制台', icon: 'HomeFilled' } },
      { path: 'category', name: 'Category', component: () => import('@/views/category/Index.vue'), meta: { title: '分类管理', icon: 'Grid' } },
      { path: 'software', name: 'Software', component: () => import('@/views/software/Index.vue'), meta: { title: '软件管理', icon: 'Document' } },
      { path: 'software/edit/:id?', name: 'SoftwareEdit', component: () => import('@/views/software/Edit.vue'), meta: { title: '编辑软件', hidden: true } },
      { path: 'users', name: 'Users', component: () => import('@/views/user/Index.vue'), meta: { title: '会员管理', icon: 'User' } },
      { path: 'pointcards', name: 'PointCards', component: () => import('@/views/pointcard/Index.vue'), meta: { title: '点卡管理', icon: 'CreditCard' } },
      { path: 'recharges', name: 'Recharges', component: () => import('@/views/recharge/Index.vue'), meta: { title: '充值记录', icon: 'Money' } },
      { path: 'ads', name: 'Ads', component: () => import('@/views/ad/Index.vue'), meta: { title: '广告管理', icon: 'Promotion' } },
      { path: 'templates', name: 'Templates', component: () => import('@/views/template/Index.vue'), meta: { title: '模板变量', icon: 'EditPen' } },
      { path: 'system', name: 'System', component: () => import('@/views/system/Index.vue'), meta: { title: '系统配置', icon: 'Setting' } },
      // Phase 4 新增模块
      { path: 'db', name: 'Db', component: () => import('@/views/db/Index.vue'), meta: { title: '数据库备份', icon: 'Coin' } },
      { path: 'static', name: 'Static', component: () => import('@/views/static/Index.vue'), meta: { title: '静态生成', icon: 'Files' } },
      { path: 'integration', name: 'Integration', component: () => import('@/views/integration/Index.vue'), meta: { title: '整合配置', icon: 'Connection' } },
      { path: 'search', name: 'Search', component: () => import('@/views/search/Index.vue'), meta: { title: '搜索分析', icon: 'Search' } },
      // Phase 5 支付模块
      { path: 'pay/channels', name: 'PayChannels', component: () => import('@/views/pay/Channel.vue'), meta: { title: '支付渠道', icon: 'BankCard' } },
      { path: 'pay/packages', name: 'PayPackages', component: () => import('@/views/pay/Package.vue'), meta: { title: '充值套餐', icon: 'Present' } },
    ],
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - 帝国下载管理后台` : '帝国下载管理后台'
  const auth = useAuthStore()
  if (to.path !== '/login' && !auth.token) {
    next('/login')
  } else {
    next()
  }
})

export default router
