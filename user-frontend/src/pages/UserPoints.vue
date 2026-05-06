<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getPointsLog } from '@/api/user'

const loading = ref(false)
const tableData = ref([])
const pagination = reactive({
  currentPage: 1,
  pageSize: 15,
  total: 0,
})

const typeMap = {
  recharge: '充值',
  download: '下载',
  checkin: '签到',
  admin_adjust: '管理员调整',
}

const typeTagMap = {
  recharge: 'success',
  download: 'warning',
  checkin: '',
  admin_adjust: 'info',
}

const amountSign = (row) => {
  if (row.type === 'recharge' || row.type === 'checkin' || row.type === 'admin_adjust') {
    return row.amount > 0 ? '+' : ''
  }
  return row.amount > 0 ? '-' : ''
}

const amountClass = (row) => {
  if (row.type === 'recharge' || row.type === 'checkin' || row.type === 'admin_adjust') {
    return row.amount >= 0 ? 'amount-positive' : 'amount-negative'
  }
  return row.amount > 0 ? 'amount-spend' : 'amount-negative'
}

async function fetchData() {
  loading.value = true
  try {
    const res = await getPointsLog(pagination.currentPage)
    // 兼容多种返回格式
    const items = res.data || res.list || res.logs || res.items || []
    const total = res.total || res.count || res.pagination?.total || items.length

    // 如果返回的是带分页的对象
    if (Array.isArray(res.data)) {
      tableData.value = res.data
      pagination.total = res.total || 0
    } else if (Array.isArray(res)) {
      tableData.value = res
      pagination.total = res.length
    } else if (res.data && res.data.items) {
      tableData.value = res.data.items
      pagination.total = res.data.total || 0
    } else if (res.data && res.data.list) {
      tableData.value = res.data.list
      pagination.total = res.data.total || 0
    } else {
      tableData.value = items
      pagination.total = total
    }
  } catch (e) {
    tableData.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.currentPage = page
  fetchData()
}

function formatTime(timestamp) {
  if (!timestamp) return '-'
  const d = new Date(timestamp)
  if (isNaN(d.getTime())) return timestamp
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="points-page">
    <div class="page-header">
      <h2>积分记录</h2>
      <p class="page-desc">查看您的积分变动明细</p>
    </div>

    <div class="table-wrapper" v-loading="loading">
      <el-table
        :data="tableData"
        stripe
        style="width: 100%"
        :empty-text="loading ? ' ' : '暂无积分记录'"
        size="default"
      >
        <el-table-column label="时间" width="180">
          <template #default="{ row }">
            <span class="cell-time">{{ formatTime(row.created_at || row.time) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="类型" width="130">
          <template #default="{ row }">
            <el-tag
              :type="typeTagMap[row.type] || 'info'"
              size="small"
              effect="plain"
            >
              {{ typeMap[row.type] || row.type || '-' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="变动数量" width="120" align="right">
          <template #default="{ row }">
            <span :class="amountClass(row)">
              {{ amountSign(row) }}{{ row.amount ?? 0 }}
            </span>
          </template>
        </el-table-column>

        <el-table-column label="余额" width="120" align="right">
          <template #default="{ row }">
            <span class="cell-balance">{{ row.balance ?? '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="备注" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="cell-remark">{{ row.remark || row.note || row.description || '-' }}</span>
          </template>
        </el-table-column>
      </el-table>

      <!-- 空数据 -->
      <div v-if="!loading && tableData.length === 0" class="empty-state">
        <el-empty description="暂无积分变动记录">
          <template #image>
            <el-icon :size="80" color="#dcdfe6"><Coin /></el-icon>
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
          @current-change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.points-page {
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

.table-wrapper {
  min-height: 200px;
}

.cell-time {
  font-size: 13px;
  color: #606266;
  white-space: nowrap;
}

.cell-balance {
  font-weight: 500;
  color: #303133;
}

.cell-remark {
  color: #909399;
  font-size: 13px;
}

.amount-positive {
  color: #67c23a;
  font-weight: 600;
  font-size: 14px;
}

.amount-spend {
  color: #e6a23c;
  font-weight: 600;
  font-size: 14px;
}

.amount-negative {
  color: #f56c6c;
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

  .table-wrapper :deep(.el-table) {
    font-size: 13px;
  }
}
</style>
