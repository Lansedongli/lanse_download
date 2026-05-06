<template>
  <div class="recharge-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>充值记录</span>
          <el-button type="primary" @click="openManualDialog">手动充值</el-button>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户ID">
          <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable style="width: 150px" />
        </el-form-item>
        <el-form-item label="充值类型">
          <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 140px">
            <el-option label="在线支付" value="online" />
            <el-option label="卡密充值" value="card" />
            <el-option label="手动充值" value="manual" />
            <el-option label="系统赠送" value="gift" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="成功" :value="1" />
            <el-option label="失败" :value="0" />
            <el-option label="待处理" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="searchForm.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 表格 -->
      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="order_no" label="订单号" width="220" />
        <el-table-column prop="user_id" label="用户ID" width="80" align="center" />
        <el-table-column prop="type" label="充值类型" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ typeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="amount" label="金额" width="100" align="center" />
        <el-table-column prop="points" label="点数" width="100" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status)" size="small">{{ statusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="时间" width="170" />
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.page_size"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="fetchList"
          @size-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 手动充值弹窗 -->
    <el-dialog
      v-model="manualDialogVisible"
      title="手动充值"
      width="480px"
      :close-on-click-modal="false"
      @closed="resetManualForm"
    >
      <el-form ref="manualFormRef" :model="manualForm" :rules="manualRules" label-width="100px">
        <el-form-item label="用户ID" prop="user_id">
          <el-input v-model="manualForm.user_id" placeholder="请输入用户ID" />
        </el-form-item>
        <el-form-item label="充值类型" prop="type">
          <el-select v-model="manualForm.type" placeholder="请选择充值类型" style="width: 100%">
            <el-option label="手动充值" value="manual" />
            <el-option label="系统赠送" value="gift" />
            <el-option label="补偿" value="compensate" />
          </el-select>
        </el-form-item>
        <el-form-item label="点数" prop="points">
          <el-input-number v-model="manualForm.points" :min="0" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="金额" prop="amount">
          <el-input-number v-model="manualForm.amount" :min="0" :precision="2" style="width: 100%" placeholder="0表示不涉及金额" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="manualDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="manualSaving" @click="handleManualRecharge">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getList, manualRecharge } from '@/api/recharge'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const tableData = ref([])

// 搜索
const searchForm = reactive({
  user_id: '',
  type: null,
  status: null,
  dateRange: null
})

const pagination = reactive({
  page: 1,
  page_size: 20,
  total: 0
})

// 类型标签
const typeMap = { online: '在线支付', card: '卡密充值', manual: '手动充值', gift: '系统赠送', compensate: '补偿' }
function typeLabel(type) { return typeMap[type] || type || '--' }

// 状态
const statusMap = { 1: '成功', 0: '失败', 2: '待处理' }
function statusLabel(status) { return statusMap[status] ?? '--' }
function statusTagType(status) {
  if (status === 1) return 'success'
  if (status === 0) return 'danger'
  if (status === 2) return 'warning'
  return 'info'
}

async function fetchList() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.page_size,
      user_id: searchForm.user_id || undefined,
      type: searchForm.type || undefined,
      status: searchForm.status || undefined
    }
    if (searchForm.dateRange && searchForm.dateRange.length === 2) {
      params.start_date = searchForm.dateRange[0]
      params.end_date = searchForm.dateRange[1]
    }
    const res = await getList(params)
    const data = Array.isArray(res) ? res : (res.list || res.data || [])
    tableData.value = data
    pagination.total = res.total ?? res.pagination?.total ?? data.length
  } catch {
    // 错误已在拦截器处理
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function handleReset() {
  searchForm.user_id = ''
  searchForm.type = null
  searchForm.status = null
  searchForm.dateRange = null
  pagination.page = 1
  fetchList()
}

// ---------- 手动充值 ----------
const manualDialogVisible = ref(false)
const manualSaving = ref(false)
const manualFormRef = ref(null)

const manualForm = reactive({
  user_id: '',
  type: 'manual',
  points: 0,
  amount: 0
})

const manualRules = {
  user_id: [{ required: true, message: '请输入用户ID', trigger: 'blur' }],
  type: [{ required: true, message: '请选择充值类型', trigger: 'change' }],
  points: [{ required: true, message: '请输入点数', trigger: 'blur' }]
}

function openManualDialog() {
  manualForm.user_id = ''
  manualForm.type = 'manual'
  manualForm.points = 0
  manualForm.amount = 0
  manualDialogVisible.value = true
}

function resetManualForm() {
  manualFormRef.value?.resetFields()
}

async function handleManualRecharge() {
  const valid = await manualFormRef.value.validate().catch(() => false)
  if (!valid) return
  manualSaving.value = true
  try {
    await manualRecharge({
      user_id: manualForm.user_id,
      type: manualForm.type,
      points: manualForm.points,
      amount: manualForm.amount
    })
    ElMessage.success('手动充值成功')
    manualDialogVisible.value = false
    fetchList()
  } catch {
    // 错误已在拦截器处理
  } finally {
    manualSaving.value = false
  }
}

onMounted(fetchList)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
.search-form { margin-bottom: 16px; }
.pagination-wrapper { display: flex; justify-content: flex-end; margin-top: 16px; }
</style>
