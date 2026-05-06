<template>
  <div class="package-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>充值套餐管理</span>
          <el-button type="primary" @click="openDialog(null)">新增套餐</el-button>
        </div>
      </template>

      <el-table
        :data="tableData"
        border
        stripe
        v-loading="loading"
      >
        <el-table-column prop="name" label="套餐名称" min-width="160" />
        <el-table-column prop="amount" label="金额（元）" width="110" align="center" />
        <el-table-column prop="points" label="点数" width="90" align="center" />
        <el-table-column prop="gift_points" label="赠送点数" width="100" align="center" />
        <el-table-column label="热推" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_hot ? 'danger' : 'info'" size="small">
              {{ row.is_hot ? '热推' : '普通' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              :loading="row._toggling"
              @change="(val) => handleToggleStatus(row, val)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" align="center" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openDialog(row)">编辑</el-button>
            <el-popconfirm title="确定要删除该充值套餐吗？" @confirm="handleDelete(row.id)">
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
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

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="540px"
      :close-on-click-modal="false"
      @closed="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
        <el-form-item label="套餐名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入套餐名称" />
        </el-form-item>
        <el-form-item label="金额" prop="amount">
          <el-input-number v-model="form.amount" :min="0" :precision="2" style="width: 100%" placeholder="请输入套餐金额" />
        </el-form-item>
        <el-form-item label="点数" prop="points">
          <el-input-number v-model="form.points" :min="0" :precision="0" style="width: 100%" placeholder="请输入套餐点数" />
        </el-form-item>
        <el-form-item label="赠送点数">
          <el-input-number v-model="form.gift_points" :min="0" :precision="0" style="width: 100%" placeholder="0表示无赠送" />
        </el-form-item>
        <el-form-item label="赠送会员组ID">
          <el-input-number v-model="form.gift_group_id" :min="0" :precision="0" style="width: 100%" placeholder="0表示不赠送会员组" />
        </el-form-item>
        <el-form-item label="赠送天数">
          <el-input-number v-model="form.gift_days" :min="0" :precision="0" style="width: 100%" placeholder="0表示不赠送天数" />
        </el-form-item>
        <el-form-item label="是否热推">
          <el-switch v-model="form.is_hot" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { getPackages, createPackage, updatePackage, deletePackage } from '@/api/payment'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const tableData = ref([])

const pagination = reactive({
  page: 1,
  page_size: 20,
  total: 0
})

// 弹窗
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const editingId = ref(null)

const form = reactive({
  name: '',
  amount: 0,
  points: 0,
  gift_points: 0,
  gift_group_id: 0,
  gift_days: 0,
  is_hot: false,
  sort: 0
})

const rules = {
  name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
  amount: [{ required: true, message: '请输入套餐金额', trigger: 'blur' }],
  points: [{ required: true, message: '请输入套餐点数', trigger: 'blur' }],
  sort: [{ required: true, message: '请输入排序值', trigger: 'blur' }]
}

const dialogTitle = computed(() => editingId.value ? '编辑充值套餐' : '新增充值套餐')

async function fetchList() {
  loading.value = true
  try {
    const res = await getPackages()
    const list = Array.isArray(res) ? res : (res.list || res.data || [])
    tableData.value = list.map(item => ({
      ...item,
      _toggling: false
    }))
    pagination.total = res.total ?? res.pagination?.total ?? list.length
  } catch {
    // 错误已在拦截器处理
  } finally {
    loading.value = false
  }
}

function openDialog(row) {
  if (row) {
    editingId.value = row.id
    form.name = row.name || ''
    form.amount = row.amount ?? 0
    form.points = row.points ?? 0
    form.gift_points = row.gift_points ?? 0
    form.gift_group_id = row.gift_group_id ?? 0
    form.gift_days = row.gift_days ?? 0
    form.is_hot = !!row.is_hot
    form.sort = row.sort ?? 0
  } else {
    editingId.value = null
    form.name = ''
    form.amount = 0
    form.points = 0
    form.gift_points = 0
    form.gift_group_id = 0
    form.gift_days = 0
    form.is_hot = false
    form.sort = 0
  }
  dialogVisible.value = true
}

function resetForm() {
  formRef.value?.resetFields()
  editingId.value = null
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    const data = {
      ...form,
      is_hot: form.is_hot ? 1 : 0
    }
    if (editingId.value) {
      await updatePackage(editingId.value, data)
      ElMessage.success('更新成功')
    } else {
      await createPackage(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch {
    // 错误已在拦截器处理
  } finally {
    saving.value = false
  }
}

async function handleToggleStatus(row, value) {
  row._toggling = true
  try {
    await updatePackage(row.id, { status: value ? 1 : 0 })
    row.status = value ? 1 : 0
    ElMessage.success(value ? '套餐已启用' : '套餐已禁用')
  } catch {
    // 错误已在拦截器处理
  } finally {
    row._toggling = false
  }
}

async function handleDelete(id) {
  try {
    await deletePackage(id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 错误已在拦截器处理
  }
}

onMounted(fetchList)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
.pagination-wrapper { display: flex; justify-content: flex-end; margin-top: 16px; }
</style>
