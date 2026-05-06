<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getSoftwareList } from '@/api/software'
import { useAppStore } from '@/stores/app'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()

// 查询参数
const query = reactive({
  category_id: route.query.category_id || '',
  page: Number(route.query.page) || 1,
  pageSize: 12,
  sort: route.query.sort || 'newest',
})

const sortOptions = [
  { label: '最新发布', value: 'newest' },
  { label: '最热下载', value: 'hot' },
  { label: '评分最高', value: 'rating' },
  { label: '免费优先', value: 'free' },
]

const softwareList = ref([])
const total = ref(0)
const loading = ref(false)

onMounted(() => {
  appStore.fetchCategories()
  fetchList()
})

watch(() => route.query, (newQuery) => {
  query.category_id = newQuery.category_id || ''
  query.page = Number(newQuery.page) || 1
  query.sort = newQuery.sort || 'newest'
  fetchList()
})

async function fetchList() {
  loading.value = true
  try {
    const params = {
      page: query.page,
      pageSize: query.pageSize,
      sort: query.sort,
    }
    if (query.category_id) params.category_id = query.category_id
    const res = await getSoftwareList(params)
    const list = res.data || res.list || res.software || res || []
    softwareList.value = Array.isArray(list) ? list : []
    total.value = res.total || res.total_count || softwareList.value.length
  } catch (e) {
    softwareList.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function changeCategory(catId) {
  query.category_id = catId === '' ? '' : catId
  query.page = 1
  updateRoute()
}

function changeSort(sort) {
  query.sort = sort
  query.page = 1
  updateRoute()
}

function changePage(page) {
  query.page = page
  updateRoute()
}

function updateRoute() {
  const queryObj = {}
  if (query.category_id) queryObj.category_id = query.category_id
  if (query.page > 1) queryObj.page = query.page
  if (query.sort !== 'newest') queryObj.sort = query.sort
  router.push({ name: 'SoftwareList', query: queryObj })
}

function goDetail(id) {
  router.push({ name: 'SoftwareDetail', params: { id } })
}
</script>

<template>
  <div class="software-list-page">
    <div class="list-layout">
      <!-- 左侧分类 -->
      <aside class="category-sidebar">
        <h3 class="sidebar-title">软件分类</h3>
        <el-menu class="category-menu" :default-active="query.category_id || ''">
          <el-menu-item index="" @click="changeCategory('')">全部软件</el-menu-item>
          <template v-for="cat in appStore.categories" :key="cat.id">
            <el-menu-item :index="String(cat.id)" @click="changeCategory(cat.id)">
              {{ cat.name }}
            </el-menu-item>
            <template v-if="cat.children">
              <el-menu-item
                v-for="child in cat.children"
                :key="child.id"
                :index="String(child.id)"
                @click="changeCategory(child.id)"
                class="sub-cat"
              >
                {{ child.name }}
              </el-menu-item>
            </template>
          </template>
        </el-menu>
      </aside>

      <!-- 右侧列表 -->
      <div class="list-main">
        <!-- 排序栏 -->
        <div class="list-toolbar">
          <div class="result-count">共 {{ total }} 个软件</div>
          <div class="sort-bar">
            <el-radio-group v-model="query.sort" @change="changeSort">
              <el-radio-button
                v-for="opt in sortOptions"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}
              </el-radio-button>
            </el-radio-group>
          </div>
        </div>

        <!-- 列表 -->
        <div v-loading="loading" class="list-content">
          <el-empty v-if="!loading && softwareList.length === 0" description="暂无软件" />

          <div v-else class="software-grid">
            <div
              v-for="item in softwareList"
              :key="item.id"
              class="software-card"
              @click="goDetail(item.id)"
            >
              <div class="card-image">
                <img :src="item.icon || item.image_url || 'https://picsum.photos/200/200'" :alt="item.name" />
              </div>
              <div class="card-body">
                <h3 class="card-title">{{ item.name }}</h3>
                <p class="card-desc">{{ item.description || item.summary || '暂无简介' }}</p>
              </div>
              <div class="card-footer">
                <span class="card-points" v-if="item.points !== undefined">
                  {{ item.points === 0 ? '免费' : item.points + ' 积分' }}
                </span>
                <span class="card-meta">
                  <span v-if="item.downloads">📥 {{ item.downloads }}</span>
                  <span v-if="item.rating">⭐ {{ item.rating }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 分页 -->
        <div class="pagination-wrap" v-if="total > query.pageSize">
          <el-pagination
            v-model:current-page="query.page"
            :page-size="query.pageSize"
            :total="total"
            layout="prev, pager, next"
            @current-change="changePage"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.software-list-page {
  padding-bottom: 40px;
}

.list-layout {
  display: flex;
  gap: 20px;
}

/* 左侧分类 */
.category-sidebar {
  width: 200px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  padding: 16px 0;
  align-self: flex-start;
}

.sidebar-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
  padding: 0 16px;
  margin: 0 0 12px;
}

.category-menu {
  border-right: none;
}

.sub-cat {
  padding-left: 40px !important;
}

/* 右侧列表 */
.list-main {
  flex: 1;
}

.list-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}

.result-count {
  font-size: 14px;
  color: #909399;
}

/* 软件网格 */
.software-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.software-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.software-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.card-image {
  width: 100%;
  height: 140px;
  background: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-image img {
  width: 72px;
  height: 72px;
  object-fit: contain;
}

.card-body {
  padding: 12px 16px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 6px;
}

.card-desc {
  font-size: 13px;
  color: #909399;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin: 0;
}

.card-footer {
  padding: 10px 16px;
  border-top: 1px solid #f2f2f2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
}

.card-points {
  color: #e6a23c;
  font-weight: 500;
}

.card-meta {
  color: #c0c4cc;
  display: flex;
  gap: 8px;
}

.pagination-wrap {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

/* 响应式 */
@media (max-width: 992px) {
  .software-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .list-layout {
    flex-direction: column;
  }

  .category-sidebar {
    width: 100%;
  }

  .category-menu {
    display: flex;
    flex-wrap: wrap;
  }

  .category-menu :deep(.el-menu-item) {
    height: 36px;
    line-height: 36px;
    font-size: 13px;
  }

  .software-grid {
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }
}

@media (max-width: 480px) {
  .software-grid {
    grid-template-columns: 1fr;
  }
}
</style>
