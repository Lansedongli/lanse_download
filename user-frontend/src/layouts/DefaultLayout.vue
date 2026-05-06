<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const appStore = useAppStore()

const searchKeyword = ref('')
const activeMenu = computed(() => {
  if (route.path.startsWith('/software')) return '/software'
  return route.path
})

onMounted(() => {
  appStore.fetchCategories()
})

function handleSearch() {
  const keyword = searchKeyword.value.trim()
  if (!keyword) return
  router.push({ name: 'Search', query: { keyword } })
}

function goHome() {
  router.push('/')
}

function goSoftware() {
  router.push('/software')
}

function goLogin() {
  router.push('/login')
}

function goRegister() {
  router.push('/register')
}

function goUser(path) {
  router.push('/user/' + path)
}

async function handleLogout() {
  await authStore.logout()
  ElMessage.success('已退出登录')
}
</script>

<template>
  <div class="default-layout">
    <!-- 顶部导航 -->
    <header class="header">
      <div class="header-inner">
        <div class="logo" @click="goHome">
          <span class="logo-icon">🏰</span>
          <span class="logo-text">帝国下载站</span>
        </div>

        <nav class="nav-menu">
          <el-menu
            :default-active="activeMenu"
            mode="horizontal"
            :ellipsis="false"
            class="top-menu"
          >
            <el-menu-item index="/" @click="goHome">首页</el-menu-item>
            <el-sub-menu v-if="appStore.categories.length" index="categories">
              <template #title>软件分类</template>
              <el-menu-item
                v-for="cat in appStore.categories"
                :key="cat.id"
                @click="router.push({ name: 'SoftwareList', query: { category_id: cat.id } })"
              >
                {{ cat.name }}
                <template v-if="cat.children && cat.children.length">
                  <el-menu-item
                    v-for="child in cat.children"
                    :key="child.id"
                    @click="router.push({ name: 'SoftwareList', query: { category_id: child.id } })"
                  >
                    {{ child.name }}
                  </el-menu-item>
                </template>
              </el-menu-item>
            </el-sub-menu>
            <el-menu-item index="/software" @click="goSoftware">全部软件</el-menu-item>
          </el-menu>
        </nav>

        <div class="header-right">
          <div class="search-box">
            <el-input
              v-model="searchKeyword"
              placeholder="搜索软件..."
              :prefix-icon="Search"
              clearable
              @keyup.enter="handleSearch"
            />
          </div>

          <template v-if="authStore.isLoggedIn">
            <el-dropdown trigger="click">
              <div class="user-avatar">
                <el-avatar :size="32" icon="UserFilled" />
                <span class="username">{{ authStore.user?.username || '用户' }}</span>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="goUser('profile')">个人中心</el-dropdown-item>
                  <el-dropdown-item @click="goUser('points')">我的积分</el-dropdown-item>
                  <el-dropdown-item @click="goUser('favorites')">我的收藏</el-dropdown-item>
                  <el-dropdown-item @click="goUser('downloads')">下载记录</el-dropdown-item>
                  <el-dropdown-item @click="goUser('recharge')">点卡充值</el-dropdown-item>
                  <el-dropdown-item divided @click="handleLogout">退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
          <template v-else>
            <div class="auth-links">
              <el-button text @click="goLogin">登录</el-button>
              <el-button type="primary" size="small" @click="goRegister">注册</el-button>
            </div>
          </template>
        </div>
      </div>
    </header>

    <!-- 主内容区 -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- 底部 -->
    <footer class="footer">
      <div class="footer-inner">
        <p>&copy; {{ new Date().getFullYear() }} 帝国下载站 - 安全、高速的软件下载平台</p>
        <p class="footer-sub">本站所有资源均来源于网络，仅供学习交流使用</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.default-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

/* 顶部导航 */
.header {
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  padding: 0 20px;
  height: 60px;
}

.logo {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 8px;
  flex-shrink: 0;
  margin-right: 24px;
}

.logo-icon {
  font-size: 28px;
}

.logo-text {
  font-size: 20px;
  font-weight: 700;
  color: #409eff;
  white-space: nowrap;
}

.nav-menu {
  flex: 1;
  overflow: hidden;
}

.top-menu {
  border-bottom: none !important;
}

.top-menu :deep(.el-menu-item) {
  height: 60px;
  line-height: 60px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.search-box {
  width: 220px;
}

.user-avatar {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.username {
  font-size: 14px;
  color: #333;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.auth-links {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* 主内容 */
.main-content {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 20px;
  box-sizing: border-box;
}

/* 底部 */
.footer {
  background: #2c3e50;
  color: #b0bec5;
  padding: 24px 20px;
  text-align: center;
}

.footer-inner p {
  margin: 4px 0;
}

.footer-sub {
  font-size: 12px;
  opacity: 0.7;
}

/* 响应式 */
@media (max-width: 768px) {
  .header-inner {
    height: auto;
    flex-wrap: wrap;
    padding: 10px 12px;
    gap: 8px;
  }

  .nav-menu {
    order: 3;
    width: 100%;
  }

  .top-menu :deep(.el-menu-item) {
    height: 44px;
    line-height: 44px;
  }

  .header-right {
    gap: 8px;
  }

  .search-box {
    width: 140px;
  }

  .main-content {
    padding: 12px;
  }
}
</style>
