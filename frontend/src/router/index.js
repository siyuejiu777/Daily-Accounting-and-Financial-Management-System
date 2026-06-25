import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue')
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/categories',
    name: 'Categories',
    component: () => import('@/views/Categories.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/records/:categoryId?',
    name: 'Records',
    component: () => import('@/views/Records.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/record/:id?',
    name: 'RecordForm',
    component: () => import('@/views/RecordForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/statistics',
    name: 'Statistics',
    component: () => import('@/views/Statistics.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/',
    redirect: '/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 简单的路由守卫：检查是否登录
router.beforeEach((to, from) => {
  const isLoggedIn = localStorage.getItem('isLoggedIn')
  if (to.meta.requiresAuth && !isLoggedIn) {
    // 返回 '/login' 表示重定向到登录页
    return '/login'
  }
  // 不需要调用 next()，什么都不返回或者返回 true 即可放行
})

export default router