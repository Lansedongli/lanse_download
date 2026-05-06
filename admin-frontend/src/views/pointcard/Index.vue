<template>
  <div class="pointcard-page">
    <!-- 批次管理 -->
    <el-card shadow="hover" class="batch-card">
      <template #header>
        <div class="page-header">
          <span>点卡批次</span>
          <div>
            <el-button type="primary" @click="openCreateBatchDialog">新建批次</el-button>
          </div>
        </div>
      </template>

      <el-table
        :data="batchList"
        border
        stripe
        v-loading="batchLoading"
        highlight-current-row
        @row-click="handleBatchClick"
      >
        <el-table-column prop="batch_no" label="批次号" width="200" />
        <el-table-column prop="total" label="总数" width="100" align="center" />
        <el-table-column prop="used" label="已使用" width="100" align="center" />
        <el-table-column prop="points" label="点数" width="100" align="center" />
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="180" align="center">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click.stop="openGenerateDialog(row)">生成卡密</el-button>
            <el-button link type="success" size="small" @click.stop="handleExport(row)">导出</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 卡密列表 -->
    <el-card v-if="selectedBatch" shadow="hover" class="card-list-card">
      <template #header>
        <div class="page-header">
          <span>卡密列表 — 批次：{{ selectedBatch.batch_no }}</span>
        </div>
      </template>

      <el-table :data="cardList" border stripe v-loading="cardLoading">
        <el-table-column prop="card_no" label="卡密" min-width="200" />
        <el-table-column prop="points" label="点数" width="100" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : row.status === 0 ? 'info' : 'warning'" size="small">
              {{ row.status === 1 ? '已使用' : row.status === 0 ? '未使用' : '已过期' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="used_user" label="使用用户" width="120" align="center" />
        <el-table-column prop="used_time" label="使用时间" width="170" />
      </el-table>
    </el-card>

    <!-- 新建批次弹窗 -->
    <el-dialog
      v-model="batchDialogVisible"
      title="新建批次"
      width="460px"
      :close-on-click-modal="false"
      @closed="resetBatchForm"
    >
      <el-form ref="batchFormRef" :model="batchForm" :rules="batchRules" label-width="120px">
        <el-form-item label="卡密数量" prop="count">
          <el-input-number v-model="batchForm.count" :min="1" :max="100000" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="点数" prop="points">
          <el-input-number v-model="batchForm.points" :min="1" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="过期天数" prop="expire_days">
          <el-input-number v-model="batchForm.expire_days" :min="0" :precision="0" style="width: 100%" placeholder="0表示永不过期" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="batchSaving" @click="handleCreateBatch">确定</el-button>
      </template>
    </el-dialog>

    <!-- 生成卡密弹窗 -->
    <el-dialog
      v-model="generateDialogVisible"
      title="生成卡密"
      width="460px"
      :close-on-click-modal="false"
      @closed="resetGenerateForm"
    >
      <el-form ref="generateFormRef" :model="generateForm" :rules="generateRules" label-width="120px">
        <el-form-item label="批次">
          <span>{{ generateTarget?.batch_no }}</span>
        </el-form-item>
        <el-form-item label="卡密数量" prop="count">
          <el-input-number v-model="generateForm.count" :min="1" :max="100000" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="点数" prop="points">
          <el-input-number v-model="generateForm.points" :min="1" :precision="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="过期天数" prop="expire_days">
          <el-input-number v-model="generateForm.expire_days" :min="0" :precision="0" style="width: 100%" placeholder="0表示永不过期" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="generateDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="generateSaving" @click="handleGenerate">确定生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getBatches, createBatch, getCards, generateCards, exportCards } from '@/api/pointcard'
import { ElMessage } from 'element-plus'

// ---------- 批次 ----------
const batchLoading = ref(false)
const batchList = ref([])

async function fetchBatches() {
  batchLoading.value = true
  try {
    const res = await getBatches()
    batchList.value = Array.isArray(res) ? res : (res.list || res.data || [])
  } catch {
    // 错误已在拦截器处理
  } finally {
    batchLoading.value = false
  }
}

// ---------- 卡密列表 ----------
const selectedBatch = ref(null)
const cardLoading = ref(false)
const cardList = ref([])

async function handleBatchClick(row) {
  selectedBatch.value = row
  cardLoading.value = true
  try {
    const res = await getCards(row.id)
    cardList.value = Array.isArray(res) ? res : (res.list || res.data || [])
  } catch {
    // 错误已在拦截器处理
  } finally {
    cardLoading.value = false
  }
}

// ---------- 新建批次 ----------
const batchDialogVisible = ref(false)
const batchSaving = ref(false)
const batchFormRef = ref(null)

const batchForm = reactive({
  count: 100,
  points: 0,
  expire_days: 0
})

const batchRules = {
  count: [{ required: true, message: '请输入卡密数量', trigger: 'blur' }],
  points: [{ required: true, message: '请输入点数', trigger: 'blur' }]
}

function openCreateBatchDialog() {
  batchForm.count = 100
  batchForm.points = 0
  batchForm.expire_days = 0
  batchDialogVisible.value = true
}

function resetBatchForm() {
  batchFormRef.value?.resetFields()
}

async function handleCreateBatch() {
  const valid = await batchFormRef.value.validate().catch(() => false)
  if (!valid) return
  batchSaving.value = true
  try {
    await createBatch({
      count: batchForm.count,
      points: batchForm.points,
      expire_days: batchForm.expire_days
    })
    ElMessage.success('批次创建成功')
    batchDialogVisible.value = false
    fetchBatches()
  } catch {
    // 错误已在拦截器处理
  } finally {
    batchSaving.value = false
  }
}

// ---------- 生成卡密 ----------
const generateDialogVisible = ref(false)
const generateSaving = ref(false)
const generateFormRef = ref(null)
const generateTarget = ref(null)

const generateForm = reactive({
  count: 100,
  points: 0,
  expire_days: 0
})

const generateRules = {
  count: [{ required: true, message: '请输入卡密数量', trigger: 'blur' }],
  points: [{ required: true, message: '请输入点数', trigger: 'blur' }]
}

function openGenerateDialog(row) {
  generateTarget.value = row
  generateForm.count = 100
  generateForm.points = row.points || 0
  generateForm.expire_days = 0
  generateDialogVisible.value = true
}

function resetGenerateForm() {
  generateFormRef.value?.resetFields()
  generateTarget.value = null
}

async function handleGenerate() {
  const valid = await generateFormRef.value.validate().catch(() => false)
  if (!valid) return
  generateSaving.value = true
  try {
    await generateCards(generateTarget.value.id, {
      count: generateForm.count,
      points: generateForm.points,
      expire_days: generateForm.expire_days
    })
    ElMessage.success('卡密生成成功')
    generateDialogVisible.value = false
    fetchBatches()
    if (selectedBatch.value && selectedBatch.value.id === generateTarget.value.id) {
      handleBatchClick(selectedBatch.value)
    }
  } catch {
    // 错误已在拦截器处理
  } finally {
    generateSaving.value = false
  }
}

// ---------- 导出 ----------
async function handleExport(row) {
  try {
    const res = await exportCards(row.id)
    // 假设后端返回下载链接或 blob
    if (res.url) {
      window.open(res.url)
    } else if (res instanceof Blob) {
      const url = URL.createObjectURL(res)
      const a = document.createElement('a')
      a.href = url
      a.download = `批次${row.batch_no}_卡密导出.csv`
      a.click()
      URL.revokeObjectURL(url)
    }
    ElMessage.success('导出成功')
  } catch {
    // 错误已在拦截器处理
  }
}

onMounted(fetchBatches)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
.batch-card { margin-bottom: 20px; }
.card-list-card { margin-top: 0; }
</style>
