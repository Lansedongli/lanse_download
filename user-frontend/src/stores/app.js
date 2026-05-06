import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getCategories } from '@/api/software'

export const useAppStore = defineStore('app', () => {
  const categories = ref([])
  const siteName = ref('帝国下载站')
  const loading = ref(false)

  async function fetchCategories() {
    if (categories.value.length > 0) return
    loading.value = true
    try {
      const res = await getCategories()
      // 兼容不同的返回格式
      const list = res.categories || res.data?.categories || res.data || res || []
      categories.value = Array.isArray(list) ? list : []
    } catch (e) {
      categories.value = []
    } finally {
      loading.value = false
    }
  }

  return {
    categories,
    siteName,
    loading,
    fetchCategories,
  }
})
