<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const menuItems = [
  { label: '用户中心', path: '/user/center', icon: 'HomeFilled' },
  { label: '个人信息', path: '/user/profile', icon: 'User' },
  { label: '积分记录', path: '/user/points', icon: 'Coin' },
  { label: '我的收藏', path: '/user/favorites', icon: 'Star' },
  { label: '下载记录', path: '/user/downloads', icon: 'Download' },
  { label: '点卡充值', path: '/user/recharge', icon: 'Wallet' },
]

const activeMenu = computed(() => route.path)
</script>

<template>
  <div class="user-layout">
    <div class="user-container">
      <!-- 左侧菜单 -->
      <aside class="user-sidebar">
        <div class="user-sidebar-header">
          <el-avatar :size="48" icon="UserFilled" />
          <div class="user-sidebar-info">
            <div class="user-sidebar-name">{{ authStore.user?.username || '用户' }}</div>
            <div class="user-sidebar-points">
              积分：{{ authStore.user?.points ?? 0 }}
            </div>
          </div>
        </div>
        <el-menu
          :default-active="activeMenu"
          router
          class="user-menu"
        >
          <el-menu-item
            v-for="item in menuItems"
            :key="item.path"
            :index="item.path"
          >
            <el-icon><component :is="item.icon" /></el-icon>
            <span>{{ item.label }}</span>
          </el-menu-item>
        </el-menu>
      </aside>

      <!-- 右侧内容区 -->
      <main class="user-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.user-layout {
  min-height: calc(100vh - 60px);
  background: #f5f7fa;
  padding: 20px;
  box-sizing: border-box;
}

.user-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  gap: 20px;
}

/* 左侧菜单 */
.user-sidebar {
  width: 240px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  align-self: flex-start;
}

.user-sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid #ebeef5;
}

.user-sidebar-name {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.user-sidebar-points {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

.user-menu {
  border-right: none;
}

/* 右侧内容 */
.user-content {
  flex: 1;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  padding: 24px;
  min-height: 400px;
}

/* 响应式 */
@media (max-width: 768px) {
  .user-layout {
    padding: 12px;
  }

  .user-container {
    flex-direction: column;
  }

  .user-sidebar {
    width: 100%;
  }

  .user-menu {
    display: flex;
    flex-wrap: wrap;
  }

  .user-menu :deep(.el-menu-item) {
    flex: 1 1 auto;
    min-width: 0;
    justify-content: center;
    padding: 0 8px;
  }

  .user-content {
    padding: 16px;
  }
}
</style>
