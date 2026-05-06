<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getPointsLog } from '@/api/user'

const router = useRouter()

const loading = ref(false)
const downloadRecords = ref([])
const pagination = reactive({
  currentPage: 1,
  pageSize: 15,
  total: 0,
})

async function fetchData() {
  loading.value = true
  try {
    const res = await getPointsLog(pagination.currentPage)
    // 兼容多种返回格式
    let items = res.data || res.list || res.logs || res.items || []
    let total = res.total || res.count || res.pagination?.total || items.length

    if (res.data?.items) {
      items = res.data.items
      total = res.data.total || 0
    } else if (res.data?.list) {
      items = res.data.list
      total = res.data.total || 0
    }

    if (!Array.isArray(items)) {
      items = []
    }

    // 筛选下载类型的记录
    const downloadItems = items.filter(
      (item) => item.type === 'download' || item.action === 'download' || item.log_type === 'download'
    )

    downloadRecords.value = downloadItems
    // 总数为筛选后的数量（如果后端已按类型筛选则使用直接返回的 total）
    pagination.total = total || downloadItems.length
  } catch (e) {
    downloadRecords.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.currentPage = page
  fetchData()
}

function goDetail(row) {
  const softwareId = row.software_id || row.software?.id || row.target_id || 0
  if (softwareId) {
    router.push({ name: 'SoftwareDetail', params: { id: softwareId } })
  }
}

function formatTime(timestamp) {
  if (!timestamp) return '-'
  const d = new Date(timestamp)
  if (isNaN(d.getTime())) return timestamp
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

function getSoftwareName(row) {
  return row.software_name || row.software?.name || row.remark || row.note || row.description || '未知软件'
}

function getPointsCost(row) {
  const amount = row.amount ?? row.points ?? 0
  return amount > 0 ? amount : Math.abs(amount)
}

function getIPAddress(row) {
  return row.ip || row.ip_address || row.client_ip || row.user_ip || '--'
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="downloads-page">
    <div class="page-header">
      <h2>下载记录</h2>
      <p class="page-desc">查看您的软件下载历史</p>
    </div>

    <div class="table-wrapper" v-loading="loading">
      <el-table
        :data="downloadRecords"
        stripe
        style="width: 100%"
        size="default"
      >
        <template #empty>
          <div v-if="!loading" class="empty-wrapper">
            <el-empty description="暂无下载记录">
              <template #image>
                <el-icon :size="80" color="#dcdfe6"><Download /></el-icon>
              </template>
            </el-empty>
          </div>
          <span v-else>&nbsp;</span>
        </template>

        <el-table-column label="软件名称" min-width="180">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              class="software-link"
              @click="goDetail(row)"
            >
              {{ getSoftwareName(row) }}
            </el-button>
          </template>
        </el-table-column>

        <el-table-column label="下载时间" width="180">
          <template #default="{ row }">
            <span class="cell-time">{{ formatTime(row.created_at || row.time || row.download_time) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="IP 地址" width="160">
          <template #default="{ row }">
            <span class="cell-ip">{{ getIPAddress(row) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="消耗积分" width="110" align="right">
          <template #default="{ row }">
            <span class="cell-points">-{{ getPointsCost(row) }}</span>
          </template>
        </el-table-column>
      </el-table>

      <!-- 全空状态 -->
      <div v-if="!loading && downloadRecords.length === 0 && pagination.total === 0" class="empty-state">
        <el-empty description="暂无下载记录">
          <template #extra>
            <el-button type="primary" @click="router.push({ name: 'SoftwareList' })">
              去发现软件
            </el-button>
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
.downloads-page {
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

.software-link {
  font-size: 14px;
  font-weight: 500;
  padding: 0;
  height: auto;
}

.cell-time {
  font-size: 13px;
  color: #606266;
  white-space: nowrap;
}

.cell-ip {
  font-size: 13px;
  color: #909399;
  font-family: 'Courier New', monospace;
}

.cell-points {
  color: #e6a23c;
  font-weight: 600;
  font-size: 14px;
}

.empty-wrapper {
  padding: 20px 0;
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

  .software-link {
    font-size: 13px;
  }
}
</style>
