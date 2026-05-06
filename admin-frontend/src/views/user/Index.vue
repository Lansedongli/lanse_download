<template>
  <div class="user-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>会员管理</span>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户名/邮箱" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="会员组">
          <el-select v-model="searchForm.group_id" placeholder="全部" clearable style="width: 150px">
            <el-option v-for="g in groupOptions" :key="g.value" :label="g.label" :value="g.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="正常" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 表格 -->
      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="email" label="邮箱" min-width="180" />
        <el-table-column prop="group_name" label="会员组" width="100" align="center" />
        <el-table-column prop="parent_username" label="上级" width="100" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="register_time" label="注册时间" width="170" />
        <el-table-column label="操作" width="260" fixed="right" align="center">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openPointsDialog(row)">调整点数</el-button>
            <el-button link type="primary" size="small" @click="openGroupDialog(row)">切换会员组</el-button>
            <el-button
              link
              :type="row.status === 1 ? 'danger' : 'success'"
              size="small"
              @click="handleToggleBan(row)"
            >
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
          </template>
        </el-table-column>
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

    <!-- 调整点数弹窗 -->
    <el-dialog
      v-model="pointsDialogVisible"
      title="调整点数"
      width="480px"
      :close-on-click-modal="false"
      @closed="resetPointsForm"
    >
      <el-form ref="pointsFormRef" :model="pointsForm" :rules="pointsRules" label-width="100px">
        <el-form-item label="用户">
          <span>{{ currentUser?.username }} (ID: {{ currentUser?.id }})</span>
        </el-form-item>
        <el-form-item label="当前点数">
          <span>{{ currentUser?.points ?? 0 }}</span>
        </el-form-item>
        <el-form-item label="操作类型" prop="type">
          <el-radio-group v-model="pointsForm.type">
            <el-radio value="add">增加</el-radio>
            <el-radio value="deduct">扣除</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="点数数量" prop="amount">
          <el-input-number v-model="pointsForm.amount" :min="0" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="操作原因" prop="reason">
          <el-input v-model="pointsForm.reason" placeholder="请输入调整原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="pointsDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="pointsSaving" @click="handleAdjustPoints">确定</el-button>
      </template>
    </el-dialog>

    <!-- 切换会员组弹窗 -->
    <el-dialog
      v-model="groupDialogVisible"
      title="切换会员组"
      width="480px"
      :close-on-click-modal="false"
      @closed="resetGroupForm"
    >
      <el-form ref="groupFormRef" :model="groupForm" :rules="groupRules" label-width="100px">
        <el-form-item label="用户">
          <span>{{ currentUser?.username }} (ID: {{ currentUser?.id }})</span>
        </el-form-item>
        <el-form-item label="当前会员组">
          <span>{{ currentUser?.group_name ?? '--' }}</span>
        </el-form-item>
        <el-form-item label="目标会员组" prop="group_id">
          <el-select v-model="groupForm.group_id" placeholder="请选择会员组" style="width: 100%">
            <el-option v-for="g in groupOptions" :key="g.value" :label="g.label" :value="g.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="有效期(天)" prop="days">
          <el-input-number v-model="groupForm.days" :min="0" :precision="0" style="width: 100%" placeholder="0表示永久" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="groupDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="groupSaving" @click="handleChangeGroup">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getList, adjustPoints, changeGroup, ban } from '@/api/user'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const tableData = ref([])

// 搜索
const searchForm = reactive({
  keyword: '',
  group_id: null,
  status: null
})

// 会员组选项（模拟，实际可从接口获取）
const groupOptions = ref([
  { label: '普通会员', value: 1 },
  { label: '黄金会员', value: 2 },
  { label: '钻石会员', value: 3 },
  { label: '至尊会员', value: 4 }
])

// 分页
const pagination = reactive({
  page: 1,
  page_size: 20,
  total: 0
})

// 查询列表
async function fetchList() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.page_size,
      ...searchForm
    }
    // 过滤空值
    Object.keys(params).forEach(k => {
      if (params[k] === '' || params[k] === null) delete params[k]
    })
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
  searchForm.keyword = ''
  searchForm.group_id = null
  searchForm.status = null
  pagination.page = 1
  fetchList()
}

// ---------- 调整点数 ----------
const pointsDialogVisible = ref(false)
const pointsSaving = ref(false)
const pointsFormRef = ref(null)
const currentUser = ref(null)

const pointsForm = reactive({
  type: 'add',
  amount: 0,
  reason: ''
})

const pointsRules = {
  type: [{ required: true, message: '请选择操作类型', trigger: 'change' }],
  amount: [{ required: true, message: '请输入点数数量', trigger: 'blur' }],
  reason: [{ required: true, message: '请输入调整原因', trigger: 'blur' }]
}

function openPointsDialog(row) {
  currentUser.value = row
  pointsForm.type = 'add'
  pointsForm.amount = 0
  pointsForm.reason = ''
  pointsDialogVisible.value = true
}

function resetPointsForm() {
  pointsFormRef.value?.resetFields()
  currentUser.value = null
}

async function handleAdjustPoints() {
  const valid = await pointsFormRef.value.validate().catch(() => false)
  if (!valid) return
  pointsSaving.value = true
  try {
    await adjustPoints(currentUser.value.id, {
      type: pointsForm.type,
      amount: pointsForm.amount,
      reason: pointsForm.reason
    })
    ElMessage.success('点数调整成功')
    pointsDialogVisible.value = false
    fetchList()
  } catch {
    // 错误已在拦截器处理
  } finally {
    pointsSaving.value = false
  }
}

// ---------- 切换会员组 ----------
const groupDialogVisible = ref(false)
const groupSaving = ref(false)
const groupFormRef = ref(null)

const groupForm = reactive({
  group_id: null,
  days: 0
})

const groupRules = {
  group_id: [{ required: true, message: '请选择会员组', trigger: 'change' }],
  days: [{ required: true, message: '请输入有效天数', trigger: 'blur' }]
}

function openGroupDialog(row) {
  currentUser.value = row
  groupForm.group_id = null
  groupForm.days = 0
  groupDialogVisible.value = true
}

function resetGroupForm() {
  groupFormRef.value?.resetFields()
  currentUser.value = null
}

async function handleChangeGroup() {
  const valid = await groupFormRef.value.validate().catch(() => false)
  if (!valid) return
  groupSaving.value = true
  try {
    await changeGroup(currentUser.value.id, {
      group_id: groupForm.group_id,
      days: groupForm.days
    })
    ElMessage.success('会员组切换成功')
    groupDialogVisible.value = false
    fetchList()
  } catch {
    // 错误已在拦截器处理
  } finally {
    groupSaving.value = false
  }
}

// ---------- 禁用/启用 ----------
async function handleToggleBan(row) {
  const action = row.status === 1 ? '禁用' : '启用'
  try {
    await ElMessageBox.confirm(`确定要${action}用户「${row.username}」吗？`, '提示', { type: 'warning' })
  } catch {
    return
  }
  try {
    await ban(row.id)
    ElMessage.success(`${action}成功`)
    fetchList()
  } catch {
    // 错误已在拦截器处理
  }
}

onMounted(fetchList)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
.search-form { margin-bottom: 16px; }
.pagination-wrapper { display: flex; justify-content: flex-end; margin-top: 16px; }
</style>
