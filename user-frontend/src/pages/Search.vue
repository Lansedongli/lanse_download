<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { searchAll, getHotSearch } from '@/api/software'

const route = useRoute()
const router = useRouter()

const keyword = ref(route.query.keyword || '')
const searchType = ref('all')
const searchResult = ref([])
const loading = ref(false)
const searched = ref(false)

const hotWords = ref([])
const hotLoading = ref(false)

onMounted(() => {
  fetchHotWords()
  if (keyword.value.trim()) {
    doSearch()
  }
})

watch(() => route.query.keyword, (val) => {
  keyword.value = val || ''
  if (keyword.value.trim()) {
    doSearch()
  }
})

async function fetchHotWords() {
  hotLoading.value = true
  try {
    const res = await getHotSearch(10)
    hotWords.value = res.data || res.list || res.words || res || []
  } catch (e) {
    hotWords.value = []
  } finally {
    hotLoading.value = false
  }
}

async function doSearch() {
  const kw = keyword.value.trim()
  if (!kw) return

  loading.value = true
  searched.value = true
  try {
    const res = await searchAll({ keyword: kw, type: searchType.value })
    const list = res.data || res.list || res.results || res || []
    searchResult.value = Array.isArray(list) ? list : []
  } catch (e) {
    searchResult.value = []
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  if (!keyword.value.trim()) return
  router.push({ name: 'Search', query: { keyword: keyword.value.trim() } })
}

function clickHotWord(word) {
  const w = typeof word === 'string' ? word : word.word || word.keyword || ''
  keyword.value = w
  handleSearch()
}

function goDetail(id) {
  router.push({ name: 'SoftwareDetail', params: { id } })
}
</script>

<template>
  <div class="search-page">
    <!-- 搜索框 -->
    <section class="search-header">
      <div class="search-input-wrap">
        <el-input
          v-model="keyword"
          size="large"
          placeholder="搜索软件..."
          clearable
          @keyup.enter="handleSearch"
        >
          <template #append>
            <el-button type="primary" @click="handleSearch" :loading="loading">
              <el-icon><Search /></el-icon>
              搜索
            </el-button>
          </template>
        </el-input>
      </div>
    </section>

    <!-- 热门搜索词（未搜索时） -->
    <section class="hot-section" v-if="!searched && !loading">
      <h3 class="section-title">🔥 热门搜索</h3>
      <div v-loading="hotLoading" class="hot-tags">
        <el-tag
          v-for="(item, idx) in hotWords"
          :key="idx"
          class="hot-tag"
          @click="clickHotWord(item)"
          effect="plain"
          type="info"
        >
          {{ typeof item === 'string' ? item : item.word || item.keyword || item.name }}
        </el-tag>
        <el-empty v-if="!hotLoading && hotWords.length === 0" description="暂无热门搜索" />
      </div>
    </section>

    <!-- 搜索结果 -->
    <section class="result-section" v-if="searched">
      <h3 class="section-title">
        搜索结果：<span class="search-kw">"{{ route.query.keyword }}"</span>
        <span class="result-count">（共 {{ searchResult.length }} 个结果）</span>
      </h3>

      <div v-loading="loading" class="result-list">
        <el-empty v-if="!loading && searchResult.length === 0" description="未找到相关结果，换个关键词试试吧" />

        <div
          v-for="item in searchResult"
          :key="item.id"
          class="result-item"
          @click="goDetail(item.id)"
        >
          <div class="result-icon">
            <img :src="item.icon || item.image_url || 'https://picsum.photos/80/80'" :alt="item.name" />
          </div>
          <div class="result-info">
            <h4 class="result-name">{{ item.name }}</h4>
            <p class="result-desc">{{ item.description || item.summary || '暂无简介' }}</p>
            <div class="result-meta">
              <span v-if="item.category">📁 {{ item.category?.name || item.category }}</span>
              <span v-if="item.rating">⭐ {{ item.rating }}</span>
              <span v-if="item.downloads">📥 {{ item.downloads }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 初始提示 -->
    <section class="search-placeholder" v-if="!searched && !loading">
      <el-empty description="输入关键词搜索你想要的软件" />
    </section>
  </div>
</template>

<style scoped>
.search-page {
  padding-bottom: 40px;
}

/* 搜索头部 */
.search-header {
  margin-bottom: 24px;
}

.search-input-wrap {
  max-width: 640px;
  margin: 0 auto;
}

/* 热门搜索 */
.hot-section {
  max-width: 640px;
  margin: 0 auto 24px;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 16px;
}

.hot-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.hot-tag {
  cursor: pointer;
  font-size: 14px;
  padding: 6px 16px;
}

.hot-tag:hover {
  color: #409eff;
  border-color: #409eff;
}

/* 搜索结果 */
.result-section {
  max-width: 800px;
  margin: 0 auto;
}

.search-kw {
  color: #409eff;
}

.result-count {
  font-size: 14px;
  color: #909399;
  font-weight: 400;
}

.result-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #fff;
  border-radius: 8px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.result-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.result-icon {
  width: 64px;
  height: 64px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  background: #f5f7fa;
}

.result-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.result-info {
  flex: 1;
}

.result-name {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 6px;
}

.result-desc {
  font-size: 13px;
  color: #909399;
  margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.result-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #c0c4cc;
}

/* 空搜索提示 */
.search-placeholder {
  margin-top: 40px;
}

/* 响应式 */
@media (max-width: 768px) {
  .result-item {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .result-meta {
    justify-content: center;
  }
}
</style>
