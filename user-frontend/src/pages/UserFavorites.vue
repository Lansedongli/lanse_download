<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFavorites, removeFavorite } from '@/api/user'

const router = useRouter()

const loading = ref(false)
const favorites = ref([])
const pagination = reactive({
  currentPage: 1,
  pageSize: 12,
  total: 0,
})

async function fetchData() {
  loading.value = true
  try {
    const res = await getFavorites(pagination.currentPage)
    // 兼容多种返回格式
    const items = res.favorites || res.data?.favorites || res.data?.items || res.data || res || []
    const total = res.total || res.count || res.pagination?.total || 0

    if (Array.isArray(res.data)) {
      favorites.value = res.data
      pagination.total = res.total || 0
    } else if (res.data?.items) {
      favorites.value = res.data.items
      pagination.total = res.data.total || 0
    } else if (res.data?.list) {
      favorites.value = res.data.list
      pagination.total = res.data.total || 0
    } else {
      favorites.value = Array.isArray(items) ? items : []
      pagination.total = total || favorites.value.length
    }
  } catch (e) {
    favorites.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.currentPage = page
  fetchData()
}

async function handleRemove(fav) {
  try {
    await ElMessageBox.confirm(
      `确定要取消收藏「${fav.software?.name || fav.name || '该软件'}」吗？`,
      '取消收藏',
      {
        confirmButtonText: '确定取消',
        cancelButtonText: '我再想想',
        type: 'warning',
      }
    )
  } catch {
    return // 用户取消
  }

  const favId = fav.id
  try {
    await removeFavorite(favId)
    ElMessage.success('已取消收藏')
    // 如果当前页只剩一条且不是第一页，回到上一页
    if (favorites.value.length === 1 && pagination.currentPage > 1) {
      pagination.currentPage--
    }
    await fetchData()
  } catch (e) {
    // 错误已在拦截器中处理
  }
}

function goDetail(id) {
  const softwareId = id || 0
  if (softwareId) {
    router.push({ name: 'SoftwareDetail', params: { id: softwareId } })
  }
}

function getSoftwareName(fav) {
  return fav.software?.name || fav.name || fav.software_name || '未知软件'
}

function getSoftwareDesc(fav) {
  return fav.software?.description || fav.description || fav.software?.summary || fav.summary || ''
}

function getSoftwareIcon(fav) {
  const icon = fav.software?.icon || fav.icon || fav.software?.image_url || fav.image_url || ''
  return icon || 'https://picsum.photos/200/200?random=' + fav.id
}

function getSoftwareId(fav) {
  return fav.software_id || fav.software?.id || fav.id
}

function getCategoryName(fav) {
  return fav.software?.category?.name || fav.software?.category || fav.category || ''
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="favorites-page">
    <div class="page-header">
      <h2>我的收藏</h2>
      <p class="page-desc">您收藏的软件列表</p>
    </div>

    <!-- 错误状态 -->
    <el-result
      v-if="!loading && favorites.length === 0 && pagination.total === 0 && pagination.currentPage === 1"
      icon="info"
      title="还没有收藏任何软件"
      sub-title="浏览软件时点击收藏按钮即可添加到收藏列表"
    >
      <template #extra>
        <el-button type="primary" @click="router.push({ name: 'SoftwareList' })">
          去浏览软件
        </el-button>
      </template>
    </el-result>

    <!-- 卡片网格 -->
    <div v-else v-loading="loading" class="favorites-grid-wrapper">
      <el-row :gutter="16" class="favorites-grid">
        <el-col
          v-for="fav in favorites"
          :key="fav.id"
          :xs="24"
          :sm="12"
          :md="8"
          :lg="6"
          class="fav-col"
        >
          <el-card class="fav-card" shadow="hover" @click="goDetail(getSoftwareId(fav))">
            <div class="fav-image-wrapper">
              <el-image
                :src="getSoftwareIcon(fav)"
                fit="contain"
                class="fav-image"
                lazy
              >
                <template #error>
                  <div class="fav-image-placeholder">
                    <el-icon :size="48"><PictureFilled /></el-icon>
                  </div>
                </template>
              </el-image>
            </div>
            <div class="fav-body">
              <h3 class="fav-name">{{ getSoftwareName(fav) }}</h3>
              <p class="fav-desc" v-if="getSoftwareDesc(fav)">
                {{ getSoftwareDesc(fav).length > 60 ? getSoftwareDesc(fav).slice(0, 60) + '...' : getSoftwareDesc(fav) }}
              </p>
              <div class="fav-footer">
                <el-tag
                  v-if="getCategoryName(fav)"
                  size="small"
                  type="info"
                  effect="plain"
                >
                  {{ getCategoryName(fav) }}
                </el-tag>
                <el-button
                  type="danger"
                  size="small"
                  plain
                  @click.stop="handleRemove(fav)"
                >
                  取消收藏
                </el-button>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- 空数据（分页切换后） -->
      <div v-if="!loading && favorites.length === 0 && pagination.total > 0" class="empty-state">
        <el-empty description="暂无更多数据" />
      </div>

      <!-- 分页 -->
      <div v-if="pagination.total > pagination.pageSize" class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.currentPage"
          :page-size="pagination.pageSize"
          :total="pagination.total"
          layout="total, prev, pager, next"
          background
          @current-change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.favorites-page {
  max-width: 100%;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 6px;
}

.page-desc {
  font-size: 14px;
  color: #909399;
  margin: 0;
}

.favorites-grid-wrapper {
  min-height: 200px;
}

.favorites-grid {
  margin-left: 0 !important;
  margin-right: 0 !important;
}

.fav-col {
  margin-bottom: 16px;
}

.fav-card {
  cursor: pointer;
  border-radius: 10px;
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
  border: 1px solid #ebeef5;
  overflow: hidden;
}

.fav-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.fav-card :deep(.el-card__body) {
  padding: 0;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.fav-image-wrapper {
  width: 100%;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
  overflow: hidden;
}

.fav-image {
  width: 100%;
  height: 100%;
}

.fav-image :deep(.el-image__inner) {
  object-fit: contain;
  padding: 16px;
}

.fav-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c0c4cc;
  background: #f5f7fa;
}

.fav-body {
  padding: 14px 16px 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.fav-name {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.fav-desc {
  font-size: 13px;
  color: #909399;
  margin: 0 0 12px;
  line-height: 1.5;
  flex: 1;
  min-height: 0;
}

.fav-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: auto;
}

.empty-state {
  padding: 40px 0;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 20px;
  padding-top: 8px;
}

@media (max-width: 768px) {
  .page-header h2 {
    font-size: 18px;
  }

  .fav-image-wrapper {
    height: 140px;
  }

  .fav-name {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .fav-image-wrapper {
    height: 120px;
  }

  .fav-body {
    padding: 10px 12px 12px;
  }
}
</style>
