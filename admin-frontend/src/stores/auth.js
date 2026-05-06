import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, getAdminInfo, logout as logoutApi } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('admin_token') || '')
  const user = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)
  const username = computed(() => user.value?.username || '')
  const nickname = computed(() => user.value?.nickname || username.value)

  async function login(credentials) {
    const res = await loginApi(credentials)
    token.value = res.tokens.access_token
    user.value = res.user
    localStorage.setItem('admin_token', res.tokens.access_token)
    localStorage.setItem('admin_user', JSON.stringify(res.user))
    return res
  }

  async function fetchUser() {
    const res = await getAdminInfo()
    user.value = res
    localStorage.setItem('admin_user', JSON.stringify(res))
  }

  function logout() {
    logoutApi().catch(() => {})
    token.value = ''
    user.value = null
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_user')
  }

  return { token, user, isLoggedIn, username, nickname, login, fetchUser, logout }
})
