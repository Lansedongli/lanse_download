<template>
  <div class="integration-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>万能会员整合配置</span>
          <el-button type="primary" @click="openCreateDialog">新增配置</el-button>
        </div>
      </template>

      <el-table :data="configList" border stripe v-loading="loading" empty-text="暂无整合配置">
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="name" label="名称" min-width="140" />
        <el-table-column prop="platform" label="平台标识" width="140">
          <template #default="{ row }">
            <el-tag size="small">{{ row.platform }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              @change="(val) => handleToggle(row, val)"
              active-text="启用"
              inactive-text="禁用"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column prop="updated_at" label="更新时间" width="170" />
        <el-table-column label="操作" width="180" align="center" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openEditDialog(row)">编辑</el-button>
            <el-popconfirm
              title="确定要删除该配置吗？"
              @confirm="handleDelete(row.id)"
            >
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑配置' : '新增配置'"
      width="520px"
      :close-on-click-modal="false"
      @closed="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="如：Discuz! X3.5 整合" />
        </el-form-item>
        <el-form-item label="平台标识" prop="platform">
          <el-input v-model="form.platform" placeholder="如：discuz" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="API地址" prop="api_url">
          <el-input v-model="form.api_url" placeholder="如：https://bbs.example.com/api/member" />
        </el-form-item>
        <el-form-item label="密钥" prop="api_key">
          <el-input v-model="form.api_key" placeholder="外部系统对接密钥" show-password />
        </el-form-item>
        <el-form-item label="启停状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="可选备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getConfigs, createConfig, updateConfig, deleteConfig } from '@/api/integration'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const configList = ref([])

async function fetchList() {
  loading.value = true
  try {
    const res = await getConfigs()
    configList.value = Array.isArray(res) ? res : (res.list || [])
  } catch { /* 拦截器处理 */ } finally { loading.value = false }
}

// 增改弹窗
const dialogVisible = ref(false)
const isEdit = ref(false)
const saving = ref(false)
const formRef = ref(null)
const editId = ref(null)

const form = reactive({
  name: '',
  platform: '',
  api_url: '',
  api_key: '',
  status: 1,
  remark: ''
})

const rules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  platform: [{ required: true, message: '请输入平台标识', trigger: 'blur' }],
  api_url: [{ required: true, message: '请输入API地址', trigger: 'blur' }],
  api_key: [{ required: true, message: '请输入密钥', trigger: 'blur' }]
}

function openCreateDialog() {
  isEdit.value = false
  editId.value = null
  Object.assign(form, { name: '', platform: '', api_url: '', api_key: '', status: 1, remark: '' })
  dialogVisible.value = true
}

function openEditDialog(row) {
  isEdit.value = true
  editId.value = row.id
  Object.assign(form, {
    name: row.name || '',
    platform: row.platform || '',
    api_url: row.api_url || '',
    api_key: row.api_key || '',
    status: row.status ?? 1,
    remark: row.remark || ''
  })
  dialogVisible.value = true
}

function resetForm() {
  formRef.value?.resetFields()
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const data = { ...form }
    if (isEdit.value) {
      await updateConfig(editId.value, data)
      ElMessage.success('配置更新成功')
    } else {
      await createConfig(data)
      ElMessage.success('配置创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch { /* 拦截器处理 */ } finally { saving.value = false }
}

async function handleToggle(row, val) {
  try {
    await updateConfig(row.id, { status: val ? 1 : 0 })
    row.status = val ? 1 : 0
    ElMessage.success(val ? '已启用' : '已禁用')
  } catch { /* 拦截器处理 */ }
}

async function handleDelete(id) {
  try {
    await deleteConfig(id)
    ElMessage.success('配置已删除')
    fetchList()
  } catch { /* 拦截器处理 */ }
}

onMounted(fetchList)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
