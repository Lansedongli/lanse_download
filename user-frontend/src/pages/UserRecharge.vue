<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { rechargeCard, getRechargeRecords } from '@/api/user'

// ==================== 充值表单 ====================
const cardNo = ref('')
const cardPassword = ref('')
const submitting = ref(false)

const formRef = ref(null)

async function handleRecharge() {
  const no = cardNo.value.trim()
  const pwd = cardPassword.value.trim()

  if (!no) {
    ElMessage.warning('请输入卡号')
    return
  }
  if (!pwd) {
    ElMessage.warning('请输入卡密')
    return
  }

  submitting.value = true
  try {
    const res = await rechargeCard(no, pwd)
    const points = res.points || res.data?.points || res.earned_points || 0
    ElMessage.success(`充值成功！获得 ${points} 积分`)
    cardNo.value = ''
    cardPassword.value = ''
    // 刷新充值记录和积分的逻辑
    pagination.currentPage = 1
    await fetchRecords()
    // 刷新用户积分（触发 UserLayout 侧边栏更新）
    try {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      if (res.user) {
        authStore.user = res.user
        localStorage.setItem('user', JSON.stringify(res.user))
      } else if (res.balance !== undefined) {
        authStore.user = { ...authStore.user, points: res.balance }
        localStorage.setItem('user', JSON.stringify(authStore.user))
      } else {
        // fallback: 用当前积分 + 获得积分
        const currentPoints = authStore.user?.points ?? 0
        authStore.user = { ...authStore.user, points: currentPoints + (points || 0) }
        localStorage.setItem('user', JSON.stringify(authStore.user))
      }
    } catch {
      // non-critical
    }
  } catch (e) {
    // 错误已在拦截器中处理
  } finally {
    submitting.value = false
  }
}

// ==================== 充值记录 ====================
const recordsLoading = ref(false)
const records = ref([])
const pagination = reactive({
  currentPage: 1,
  pageSize: 15,
  total: 0,
})

async function fetchRecords() {
  recordsLoading.value = true
  try {
    const res = await getRechargeRecords(pagination.currentPage)
    // 兼容多种返回格式
    let items = res.records || res.data?.records || res.data?.items || res.data || res || []
    let total = res.total || res.count || res.pagination?.total || 0

    if (res.data?.items) {
      items = res.data.items
      total = res.data.total || 0
    } else if (res.data?.list) {
      items = res.data.list
      total = res.data.total || 0
    } else if (Array.isArray(res.data)) {
      items = res.data
      total = res.total || 0
    }

    records.value = Array.isArray(items) ? items : []
    pagination.total = total || records.value.length
  } catch (e) {
    records.value = []
    pagination.total = 0
  } finally {
    recordsLoading.value = false
  }
}

function handleRecordsPageChange(page) {
  pagination.currentPage = page
  fetchRecords()
}

function formatTime(timestamp) {
  if (!timestamp) return '-'
  const d = new Date(timestamp)
  if (isNaN(d.getTime())) return timestamp
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

function getCardNo(row) {
  return row.card_no || row.card_number || row.card || '****'
}

function getPoints(row) {
  return row.points || row.earned_points || row.amount || 0
}

onMounted(() => {
  fetchRecords()
})
</script>

<template>
  <div class="recharge-page">
    <div class="page-header">
      <h2>点卡充值</h2>
      <p class="page-desc">输入卡号与卡密进行充值</p>
    </div>

    <!-- 充值表单 -->
    <el-card shadow="never" class="recharge-form-card">
      <el-form
        ref="formRef"
        label-position="top"
        class="recharge-form"
        @submit.prevent="handleRecharge"
      >
        <el-form-item label="卡号">
          <el-input
            v-model="cardNo"
            placeholder="请输入点卡卡号"
            clearable
            :disabled="submitting"
            size="large"
          />
        </el-form-item>

        <el-form-item label="卡密">
          <el-input
            v-model="cardPassword"
            type="password"
            placeholder="请输入点卡卡密"
            show-password
            clearable
            :disabled="submitting"
            size="large"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="submitting"
            class="recharge-submit-btn"
            @click="handleRecharge"
          >
            {{ submitting ? '充值中...' : '立即充值' }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 充值记录 -->
    <div class="records-section">
      <div class="records-header">
        <h3>充值记录</h3>
      </div>

      <div class="table-wrapper" v-loading="recordsLoading">
        <el-table
          :data="records"
          stripe
          style="width: 100%"
          size="default"
        >
          <template #empty>
            <div v-if="!recordsLoading">
              <el-empty description="暂无充值记录" />
            </div>
            <span v-else>&nbsp;</span>
          </template>

          <el-table-column label="卡号" min-width="160">
            <template #default="{ row }">
              <span class="cell-card-no">{{ getCardNo(row) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="充值时间" width="180">
            <template #default="{ row }">
              <span class="cell-time">{{ formatTime(row.created_at || row.time || row.recharge_time) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="获得积分" width="120" align="right">
            <template #default="{ row }">
              <span class="cell-points">+{{ getPoints(row) }}</span>
            </template>
          </el-table-column>
        </el-table>

        <!-- 空状态 -->
        <div v-if="!recordsLoading && records.length === 0" class="empty-state">
          <el-empty description="暂无充值记录">
            <template #image>
              <el-icon :size="80" color="#dcdfe6"><Wallet /></el-icon>
            </template>
          </el-empty>
        </div>

        <!-- 分页 -->
        <div v-if="pagination.total > pagination.pageSize" class="pagination-wrapper">
          <el-pagination
            v-model:current-page="pagination.currentPage"
            :page-size="pagination.pageSize"
            :total="pagination.total"
            layout="total, prev, pager, next"
            background
            @current-change="handleRecordsPageChange"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.recharge-page {
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

/* 充值表单 */
.recharge-form-card {
  margin-bottom: 28px;
  border: none;
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  max-width: 520px;
}

.recharge-form-card :deep(.el-card__body) {
  padding: 24px;
}

.recharge-form {
  width: 100%;
}

.recharge-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #303133;
}

.recharge-submit-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
}

/* 充值记录 */
.records-section {
  margin-top: 8px;
}

.records-header {
  margin-bottom: 16px;
}

.records-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin: 0;
}

.table-wrapper {
  min-height: 200px;
}

.cell-card-no {
  font-size: 14px;
  color: #606266;
  font-family: 'Courier New', monospace;
  letter-spacing: 2px;
}

.cell-time {
  font-size: 13px;
  color: #606266;
  white-space: nowrap;
}

.cell-points {
  color: #67c23a;
  font-weight: 600;
  font-size: 14px;
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

  .recharge-form-card {
    max-width: 100%;
  }

  .recharge-form-card :deep(.el-card__body) {
    padding: 18px;
  }

  .table-wrapper :deep(.el-table) {
    font-size: 13px;
  }
}
</style>
