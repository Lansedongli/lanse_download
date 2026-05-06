<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getUserProfile } from '@/api/user'

const router = useRouter()
const authStore = useAuthStore()

const user = ref(authStore.user || {})
const loading = ref(false)

const stats = ref([
  { label: '可用积分', value: 0, icon: 'Coin', color: '#409eff' },
  { label: '收藏数', value: 0, icon: 'Star', color: '#e6a23c' },
  { label: '下载次数', value: 0, icon: 'Download', color: '#67c23a' },
  { label: '充值总额', value: '0.00', icon: 'Wallet', color: '#f56c6c' },
])

onMounted(async () => {
  loading.value = true
  try {
    const res = await getUserProfile()
    const data = res.user || res.data || res
    user.value = data
    authStore.user = data
    // 更新统计数据
    stats.value[0].value = data.points ?? 0
    stats.value[1].value = data.favorite_count ?? 0
    stats.value[2].value = data.download_count ?? 0
    stats.value[3].value = data.total_recharge ?? '0.00'
  } catch (e) {
    // 使用 store 中的缓存数据
    if (authStore.user) {
      user.value = authStore.user
      stats.value[0].value = authStore.user.points ?? 0
      stats.value[1].value = authStore.user.favorite_count ?? 0
      stats.value[2].value = authStore.user.download_count ?? 0
      stats.value[3].value = authStore.user.total_recharge ?? '0.00'
    }
  } finally {
    loading.value = false
  }
})

function goRecharge() {
  router.push({ name: 'UserRecharge' })
}
function goProfile() {
  router.push({ name: 'UserProfile' })
}
function goFavorites() {
  router.push({ name: 'UserFavorites' })
}
function goDownloads() {
  router.push({ name: 'UserDownloads' })
}

const levelTag = (points) => {
  if (points >= 10000) return { text: '钻石会员', type: 'danger' }
  if (points >= 5000) return { text: '金卡会员', type: 'warning' }
  if (points >= 1000) return { text: '银卡会员', type: 'info' }
  return { text: '普通会员', type: '' }
}
</script>

<template>
  <div class="user-center" v-loading="loading">
    <!-- 用户信息卡片 -->
    <el-card class="user-info-card" shadow="never">
      <div class="user-info-body">
        <el-avatar :size="72" class="user-avatar">
          <el-icon :size="36"><UserFilled /></el-icon>
        </el-avatar>
        <div class="user-details">
          <div class="user-name-row">
            <span class="user-name">{{ user.username || '未登录' }}</span>
            <el-tag
              :type="levelTag(user.points || 0).type"
              size="small"
              effect="dark"
              round
              class="user-level-tag"
            >
              {{ levelTag(user.points || 0).text }}
            </el-tag>
          </div>
          <div class="user-points-row">
            <el-icon class="points-icon"><Coin /></el-icon>
            <span class="points-label">积分余额：</span>
            <span class="points-value">{{ user.points ?? 0 }}</span>
          </div>
          <div class="user-meta" v-if="user.email">
            <el-icon><Message /></el-icon>
            <span>{{ user.email }}</span>
          </div>
        </div>
      </div>
    </el-card>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col
        v-for="(stat, index) in stats"
        :key="index"
        :xs="12"
        :sm="6"
        class="stats-col"
      >
        <el-card shadow="never" class="stat-card">
          <div class="stat-icon" :style="{ color: stat.color, background: stat.color + '15' }">
            <el-icon :size="24"><component :is="stat.icon" /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ stat.value }}</div>
            <div class="stat-label">{{ stat.label }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 快捷操作 -->
    <el-card shadow="never" class="actions-card">
      <template #header>
        <span class="actions-title">快捷操作</span>
      </template>
      <div class="actions-grid">
        <el-button
          type="primary"
          :icon="Wallet"
          size="large"
          class="action-btn"
          @click="goRecharge"
        >
          <span>点卡充值</span>
        </el-button>
        <el-button
          type="success"
          :icon="EditPen"
          size="large"
          class="action-btn"
          @click="goProfile"
        >
          <span>修改资料</span>
        </el-button>
        <el-button
          type="warning"
          :icon="Star"
          size="large"
          class="action-btn"
          @click="goFavorites"
        >
          <span>我的收藏</span>
        </el-button>
        <el-button
          type="info"
          :icon="Download"
          size="large"
          class="action-btn"
          @click="goDownloads"
        >
          <span>下载记录</span>
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.user-center {
  padding: 0;
}

/* 用户信息卡片 */
.user-info-card {
  margin-bottom: 20px;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
}

.user-info-card :deep(.el-card__body) {
  padding: 24px;
}

.user-info-body {
  display: flex;
  align-items: center;
  gap: 20px;
}

.user-avatar {
  background: rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
  border: 3px solid rgba(255, 255, 255, 0.5);
}

.user-avatar :deep(.el-icon) {
  color: #fff;
}

.user-details {
  flex: 1;
  min-width: 0;
}

.user-name-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.user-name {
  font-size: 22px;
  font-weight: 700;
}

.user-level-tag {
  font-size: 12px;
}

.user-points-row {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 6px;
  font-size: 15px;
}

.points-icon {
  font-size: 18px;
  color: #ffd666;
}

.points-label {
  opacity: 0.85;
}

.points-value {
  font-weight: 700;
  font-size: 18px;
  color: #ffd666;
}

.user-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  opacity: 0.8;
}

/* 统计卡片 */
.stats-row {
  margin-bottom: 20px;
  margin-left: 0 !important;
  margin-right: 0 !important;
}

.stats-col {
  margin-bottom: 16px;
}

.stat-card {
  border: none;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-top: 2px;
}

/* 快捷操作 */
.actions-card {
  border: none;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  border-radius: 10px;
}

.actions-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.action-btn {
  width: 100%;
  height: 56px;
  font-size: 15px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.action-btn span {
  font-weight: 500;
}

/* 响应式 */
@media (max-width: 768px) {
  .user-info-card :deep(.el-card__body) {
    padding: 18px;
  }

  .user-info-body {
    gap: 14px;
  }

  .user-avatar {
    --size: 56px;
    width: 56px;
    height: 56px;
  }

  .user-name {
    font-size: 18px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .action-btn {
    height: 48px;
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .user-info-body {
    flex-direction: column;
    text-align: center;
  }

  .user-name-row {
    justify-content: center;
  }

  .user-points-row {
    justify-content: center;
  }

  .user-meta {
    justify-content: center;
  }

  .stats-col {
    margin-bottom: 10px;
  }

  .stat-card :deep(.el-card__body) {
    padding: 14px 16px;
    gap: 10px;
  }

  .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
  }

  .stat-icon .el-icon {
    font-size: 20px;
  }

  .stat-value {
    font-size: 18px;
  }

  .actions-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
