<template>
  <div class="search-page">
    <el-row :gutter="20">
      <!-- 热门搜索词 -->
      <el-col :span="10">
        <el-card shadow="hover">
          <template #header>
            <div class="page-header">
              <span>热门搜索词 TOP20</span>
              <el-button link type="primary" @click="fetchHotKeywords">刷新</el-button>
            </div>
          </template>

          <el-table :data="keywords" border stripe v-loading="hotLoading" empty-text="暂无搜索数据" max-height="520">
            <el-table-column type="index" label="#" width="60" align="center" />
            <el-table-column prop="keyword" label="关键词" min-width="140">
              <template #default="{ row }">
                <el-tag>{{ row.keyword }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="count" label="搜索次数" width="110" align="center" sortable />
            <el-table-column prop="last_search" label="最后搜索" width="120" />
          </el-table>
        </el-card>
      </el-col>

      <!-- 搜索日志 -->
      <el-col :span="14">
        <el-card shadow="hover">
          <template #header>
            <div class="page-header">
              <span>实时搜索日志</span>
              <div>
                <el-input
                  v-model="searchKeyword"
                  placeholder="输入关键词搜索..."
                  clearable
                  style="width: 200px; margin-right: 12px"
                  @keyup.enter="doSearch"
                />
                <el-button type="primary" @click="doSearch" :loading="logLoading">搜索</el-button>
              </div>
            </div>
          </template>

          <el-table :data="searchResults" border stripe v-loading="logLoading" empty-text="输入关键词后搜索" max-height="480">
            <el-table-column type="index" label="#" width="60" align="center" />
            <el-table-column prop="title" label="标题" min-width="180">
              <template #default="{ row }">
                <span style="font-weight:500">{{ row.title }}</span>
                <br />
                <span style="color:#909399;font-size:12px">{{ row.description?.substring(0, 80) }}{{ row.description?.length > 80 ? '...' : '' }}</span>
              </template>
            </el-table-column>
            <el-table-column label="类型" width="90" align="center">
              <template #default="{ row }">
                <el-tag :type="row.type === 'software' ? 'success' : 'info'" size="small">
                  {{ row.type === 'software' ? '软件' : row.type === 'category' ? '分类' : row.type }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="100" align="center">
              <template #default="{ row }">
                <el-button link type="primary" size="small" @click="handlePreview(row)">预览</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <div style="margin-top: 16px; text-align: right" v-if="searchTotal > 0">
            <el-pagination
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :total="searchTotal"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              small
              @change="doSearch"
            />
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { hotKeywords, searchAll } from '@/api/search'
import { ElMessage } from 'element-plus'

// 热门搜索词
const hotLoading = ref(false)
const keywords = ref([])

async function fetchHotKeywords() {
  hotLoading.value = true
  try {
    const res = await hotKeywords(20)
    keywords.value = res.keywords || []
  } catch { /* 拦截器处理 */ } finally { hotLoading.value = false }
}

// 搜索日志
const logLoading = ref(false)
const searchKeyword = ref('')
const searchResults = ref([])
const searchTotal = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)

async function doSearch() {
  if (!searchKeyword.value.trim()) {
    return ElMessage.warning('请输入搜索关键词')
  }

  logLoading.value = true
  try {
    const res = await searchAll({
      keyword: searchKeyword.value,
      type: 'all',
      page: currentPage.value,
      pageSize: pageSize.value
    })
    searchResults.value = res.data || []
    searchTotal.value = res.total || 0
  } catch { /* 拦截器处理 */ } finally { logLoading.value = false }
}

function handlePreview(row) {
  // 简单提示，实际可打开新窗口预览
  ElMessage.info(`预览: ${row.title}`)
}

onMounted(fetchHotKeywords)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
</style>
