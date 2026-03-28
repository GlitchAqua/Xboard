import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/Login.vue'), meta: { guest: true } },
  { path: '/register', name: 'Register', component: () => import('@/views/Register.vue'), meta: { guest: true } },
  {
    path: '/',
    component: () => import('@/components/Layout.vue'),
    children: [
      { path: '', name: 'Dashboard', component: () => import('@/views/Dashboard.vue') },
      { path: 'recharge', name: 'Recharge', component: () => import('@/views/Recharge.vue') },
      { path: 'plan', name: 'Plan', component: () => import('@/views/PlanSelect.vue') },
      { path: 'traffic-package', name: 'TrafficPackage', component: () => import('@/views/TrafficPackage.vue') },
      { path: 'balance', name: 'BalanceLogs', component: () => import('@/views/BalanceLogs.vue') },
      { path: 'order', name: 'OrderList', component: () => import('@/views/OrderList.vue') },
      { path: 'order/:tradeNo', name: 'OrderDetail', component: () => import('@/views/OrderDetail.vue'), props: true },
      { path: 'server', name: 'ServerList', component: () => import('@/views/ServerList.vue') },
      { path: 'traffic', name: 'TrafficStats', component: () => import('@/views/TrafficStats.vue') },
      { path: 'ticket', name: 'TicketList', component: () => import('@/views/TicketList.vue') },
      { path: 'ticket/:id', name: 'TicketDetail', component: () => import('@/views/TicketDetail.vue'), props: true },
      { path: 'knowledge', name: 'Knowledge', component: () => import('@/views/Knowledge.vue') },
      { path: 'notice', name: 'Notices', component: () => import('@/views/Notices.vue') },
      { path: 'invite', name: 'Invite', component: () => import('@/views/Invite.vue') },
      { path: 'profile', name: 'Profile', component: () => import('@/views/Profile.vue') },
    ]
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (!to.meta.guest && !token) {
    next('/login')
  } else if (to.meta.guest && token) {
    next('/')
  } else {
    next()
  }
})

export default router
