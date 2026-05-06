import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import * as authApi from '@/api/auth'
import * as userApi from '@/api/user'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()

  // 从 localStorage 恢复
  const savedUser = localStorage.getItem('user')
  const savedToken = localStorage.getItem('token')
  const savedRefreshToken = localStorage.getItem('refreshToken')

  const user = ref(savedUser ? JSON.parse(savedUser) : null)
  const token = ref(savedToken || '')
  const refreshToken = ref(savedRefreshToken || '')

  const isLoggedIn = computed(() => !!token.value)

  function persistToLocal() {
    localStorage.setItem('user', JSON.stringify(user.value))
    localStorage.setItem('token', token.value)
    localStorage.setItem('refreshToken', refreshToken.value)
  }

  function clearLocal() {
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    localStorage.removeItem('refreshToken')
  }

  async function login(username, password) {
    const res = await authApi.login(username, password)
    user.value = res.user
    token.value = res.tokens?.access_token || res.token || res.access_token
    refreshToken.value = res.tokens?.refresh_token || res.refresh_token || ''
    persistToLocal()
    return res
  }

  async function register(username, password, email) {
    const res = await authApi.register(username, password, email)
    user.value = res.user
    token.value = res.tokens?.access_token || res.token || res.access_token
    refreshToken.value = res.tokens?.refresh_token || res.refresh_token || ''
    persistToLocal()
    return res
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (e) {
      // 即使接口失败也清除本地状态
    }
    user.value = null
    token.value = ''
    refreshToken.value = ''
    clearLocal()
    router.push('/login')
  }

  async function refreshAccessToken() {
    try {
      const res = await authApi.refreshToken(refreshToken.value)
      token.value = res.tokens?.access_token || res.token || res.access_token
      if (res.tokens?.refresh_token || res.refresh_token) {
        refreshToken.value = res.tokens?.refresh_token || res.refresh_token
      }
      persistToLocal()
      return token.value
    } catch (e) {
      user.value = null
      token.value = ''
      refreshToken.value = ''
      clearLocal()
      router.push('/login')
      throw e
    }
  }

  async function fetchProfile() {
    const res = await userApi.getUserProfile()
    user.value = res.user || res.data || res
    persistToLocal()
  }

  return {
    user,
    token,
    refreshToken,
    isLoggedIn,
    login,
    register,
    logout,
    refreshAccessToken,
    fetchProfile,
  }
})
