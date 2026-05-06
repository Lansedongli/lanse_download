<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getHotSoftware, getRecommendSoftware } from '@/api/software'

const router = useRouter()

const carouselItems = [
  {
    id: 1,
    image: 'https://picsum.photos/1200/400?random=1',
    title: '海量正版软件',
    subtitle: '帝国下载站，安全高速，一站下载',
  },
  {
    id: 2,
    image: 'https://picsum.photos/1200/400?random=2',
    title: '每日更新推荐',
    subtitle: '精选热门软件，发现好用工具',
  },
  {
    id: 3,
    image: 'https://picsum.photos/1200/400?random=3',
    title: '一键极速下载',
    subtitle: '不限速下载，VIP专享高速通道',
  },
]

const hotList = ref([])
const recommendList = ref([])
const hotLoading = ref(false)
const recommendLoading = ref(false)

onMounted(async () => {
  try {
    hotLoading.value = true
    const hotRes = await getHotSoftware(8)
    hotList.value = hotRes.data || hotRes.list || hotRes.software || hotRes || []
  } catch (e) {
    hotList.value = []
  } finally {
    hotLoading.value = false
  }

  try {
    recommendLoading.value = true
    const recRes = await getRecommendSoftware(8)
    recommendList.value = recRes.data || recRes.list || recRes.software || recRes || []
  } catch (e) {
    recommendList.value = []
  } finally {
    recommendLoading.value = false
  }
})

function goDetail(id) {
  router.push({ name: 'SoftwareDetail', params: { id } })
}

function goSoftwareList() {
  router.push({ name: 'SoftwareList' })
}

function goSearch(ev) {
  router.push({ name: 'Search' })
}
</script>

<template>
  <div class="home-page">
    <!-- 轮播图 -->
    <section class="carousel-section">
      <el-carousel :interval="4000" type="card" height="280px" arrow="always">
        <el-carousel-item v-for="item in carouselItems" :key="item.id">
          <div class="carousel-card">
            <img :src="item.image" :alt="item.title" class="carousel-image" />
            <div class="carousel-overlay">
              <h2>{{ item.title }}</h2>
              <p>{{ item.subtitle }}</p>
            </div>
          </div>
        </el-carousel-item>
      </el-carousel>
    </section>

    <!-- 快捷入口 -->
    <section class="quick-actions">
      <div class="quick-card" @click="goSoftwareList">
        <el-icon size="32"><List /></el-icon>
        <span>全部软件</span>
      </div>
      <div class="quick-card" @click="goSearch">
        <el-icon size="32"><Search /></el-icon>
        <span>搜索软件</span>
      </div>
      <div class="quick-card" @click="goSoftwareList">
        <el-icon size="32"><StarFilled /></el-icon>
        <span>热门排行</span>
      </div>
      <div class="quick-card" @click="goSoftwareList">
        <el-icon size="32"><DArrowRight /></el-icon>
        <span>最新上架</span>
      </div>
    </section>

    <!-- 热门软件 -->
    <section class="section" v-loading="hotLoading">
      <div class="section-header">
        <h2 class="section-title">🔥 热门软件</h2>
        <el-button text type="primary" @click="goSoftwareList">查看更多</el-button>
      </div>
      <el-empty v-if="!hotLoading && hotList.length === 0" description="暂无热门软件" />
      <div v-else class="software-grid">
        <div
          v-for="item in hotList"
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
            <span class="card-rating" v-if="item.rating">⭐ {{ item.rating }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- 推荐软件 -->
    <section class="section" v-loading="recommendLoading">
      <div class="section-header">
        <h2 class="section-title">✨ 精品推荐</h2>
        <el-button text type="primary" @click="goSoftwareList">查看更多</el-button>
      </div>
      <el-empty v-if="!recommendLoading && recommendList.length === 0" description="暂无推荐" />
      <div v-else class="software-grid">
        <div
          v-for="item in recommendList"
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
            <span class="card-rating" v-if="item.rating">⭐ {{ item.rating }}</span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.home-page {
  padding-bottom: 40px;
}

/* 轮播图 */
.carousel-section {
  margin-bottom: 24px;
}

.carousel-card {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 12px;
  overflow: hidden;
}

.carousel-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.carousel-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20px 30px;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.6));
  color: #fff;
}

.carousel-overlay h2 {
  margin: 0 0 6px;
  font-size: 24px;
  color: #fff;
}

.carousel-overlay p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

/* 快捷入口 */
.quick-actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.quick-card {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  color: #409eff;
}

.quick-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.2);
}

.quick-card span {
  display: block;
  margin-top: 8px;
  font-size: 14px;
  font-weight: 500;
}

/* 通用区块 */
.section {
  margin-bottom: 32px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin: 0;
}

/* 软件网格 */
.software-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
  height: 160px;
  overflow: hidden;
  background: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-image img {
  width: 80px;
  height: 80px;
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-desc {
  font-size: 13px;
  color: #909399;
  line-height: 1.5;
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
}

.card-points {
  font-size: 12px;
  color: #e6a23c;
  font-weight: 500;
}

.card-rating {
  font-size: 12px;
  color: #909399;
}

/* 响应式 */
@media (max-width: 768px) {
  .quick-actions {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .software-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .quick-card {
    padding: 16px;
  }

  .carousel-section {
    margin-bottom: 16px;
  }

  .carousel-section :deep(.el-carousel__container) {
    height: 180px !important;
  }
}

@media (max-width: 480px) {
  .software-grid {
    grid-template-columns: 1fr;
  }
}
</style>
