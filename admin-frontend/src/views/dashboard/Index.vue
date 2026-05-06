<template>
  <div class="dashboard">
    <!-- 欢迎卡片 -->
    <el-card class="welcome-card" shadow="hover">
      <div class="welcome-content">
        <div class="welcome-text">
          <h2>欢迎回来，{{ auth.nickname }}</h2>
          <p>今天是 {{ today }}，祝您工作愉快！</p>
        </div>
        <el-icon :size="64" color="#409EFF"><HomeFilled /></el-icon>
      </div>
    </el-card>

    <!-- 统计卡片 第一行 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #e6f7ff">
              <el-icon :size="32" color="#1890ff"><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.softwareCount }}</div>
              <div class="stat-label">软件总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #f6ffed">
              <el-icon :size="32" color="#52c41a"><User /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.userCount }}</div>
              <div class="stat-label">会员总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #fff7e6">
              <el-icon :size="32" color="#fa8c16"><Money /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">¥{{ stats.totalAmount }}</div>
              <div class="stat-label">累计充值</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #fce4ec">
              <el-icon :size="32" color="#eb2f96"><TrendCharts /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.totalOrders }}</div>
              <div class="stat-label">累计订单</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <!-- 统计卡片 第二行 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #f0f5ff">
              <el-icon :size="32" color="#2f54eb"><Sell /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">¥{{ stats.todayAmount }}</div>
              <div class="stat-label">今日充值</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" style="background: #f9f0ff">
              <el-icon :size="32" color="#722ed1"><List /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.todayOrders }}</div>
              <div class="stat-label">今日订单</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 快捷入口 -->
    <el-card shadow="hover" class="quick-links-card">
      <template #header><span>快捷入口</span></template>
      <el-row :gutter="16">
        <el-col :span="6" v-for="link in quickLinks" :key="link.path">
          <div class="quick-link" @click="$router.push(link.path)">
            <el-icon :size="28" :color="link.color"><component :is="link.icon" /></el-icon>
            <span>{{ link.label }}</span>
          </div>
        </el-col>
      </el-row>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getList as getSoftwareList } from '@/api/software'
import { getList as getUsers } from '@/api/user'
import { getReportSummary } from '@/api/payment'

const auth = useAuthStore()

const today = new Date().toLocaleDateString('zh-CN', {
  year: 'numeric', month: 'long', day: 'numeric', weekday: 'long'
})

const stats = reactive({
  softwareCount: 0,
  userCount: 0,
  totalAmount: 0,
  totalOrders: 0,
  todayAmount: 0,
  todayOrders: 0,
})

const quickLinks = [
  { path: '/software', label: '软件管理', icon: 'Document', color: '#409EFF' },
  { path: '/category', label: '分类管理', icon: 'Grid', color: '#67c23a' },
  { path: '/users', label: '会员管理', icon: 'User', color: '#e6a23c' },
  { path: '/recharges', label: '充值记录', icon: 'Money', color: '#f56c6c' },
  { path: '/pay/channels', label: '支付配置', icon: 'BankCard', color: '#409EFF' },
  { path: '/pay/packages', label: '套餐管理', icon: 'Present', color: '#67c23a' },
]

onMounted(async () => {
  // 软件总数
  try {
    const sw = await getSoftwareList({ page: 1, limit: 1 })
    stats.softwareCount = sw.total || sw.count || 0
  } catch { stats.softwareCount = '--' }

  // 会员总数
  try {
    const us = await getUsers({ page: 1, limit: 1 })
    stats.userCount = us.total || us.count || 0
  } catch { stats.userCount = '--' }

  // 支付报表（Phase 5）
  try {
    const sm = await getReportSummary()
    stats.totalAmount = sm.total_amount ?? 0
    stats.totalOrders = sm.total_orders ?? 0
    stats.todayAmount = sm.today_amount ?? 0
    stats.todayOrders = sm.today_orders ?? 0
  } catch { /* 报表可选 */ }
})
</script>

<style scoped>
.dashboard { max-width: 1200px; }
.welcome-card { margin-bottom: 20px; }
.welcome-content { display: flex; align-items: center; justify-content: space-between; }
.welcome-text h2 { margin: 0 0 8px 0; font-size: 22px; color: #303133; }
.welcome-text p { margin: 0; color: #909399; font-size: 14px; }

.stat-row { margin-bottom: 20px; }
.stat-card { cursor: default; }
.stat-inner { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
.stat-value { font-size: 26px; font-weight: 700; color: #303133; line-height: 1.2; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }

.quick-links-card { margin-bottom: 20px; }
.quick-link { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 0; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.quick-link:hover { background: #f5f7fa; transform: translateY(-2px); }
.quick-link span { margin-top: 8px; font-size: 14px; color: #606266; }
</style>
