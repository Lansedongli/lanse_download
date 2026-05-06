<template>
  <div class="channel-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>支付渠道管理</span>
          <el-button type="primary" @click="openDialog(null)">新增渠道</el-button>
        </div>
      </template>

      <el-table
        :data="channelList"
        border
        stripe
        v-loading="loading"
      >
        <el-table-column prop="name" label="渠道名称" min-width="140" />
        <el-table-column prop="code" label="渠道代码" width="120" />
        <el-table-column prop="app_id" label="APPID" width="180" show-overflow-tooltip />
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
            <el-popconfirm title="确定要删除该支付渠道吗？" @confirm="handleDelete(row.id)">
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
      :title="dialogTitle"
      width="600px"
      :close-on-click-modal="false"
      @closed="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="渠道名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入渠道名称" />
        </el-form-item>
        <el-form-item label="渠道代码" prop="code">
          <el-input v-model="form.code" placeholder="请输入渠道代码，如 alipay、wechat" />
        </el-form-item>
        <el-form-item label="APPID" prop="app_id">
          <el-input v-model="form.app_id" placeholder="请输入应用APPID" />
        </el-form-item>
        <el-form-item label="商户号">
          <el-input v-model="form.mch_id" placeholder="请输入商户号" />
        </el-form-item>
        <el-form-item label="API密钥">
          <el-input v-model="form.api_key" placeholder="请输入API密钥" show-password />
        </el-form-item>
        <el-form-item label="私钥">
          <el-input v-model="form.private_key" type="textarea" :rows="3" placeholder="请输入私钥内容" />
        </el-form-item>
        <el-form-item label="公钥">
          <el-input v-model="form.public_key" type="textarea" :rows="3" placeholder="请输入公钥内容" />
        </el-form-item>
        <el-form-item label="回调URL">
          <el-input v-model="form.notify_url" placeholder="请输入支付回调地址" />
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
import { getChannels, createChannel, updateChannel, deleteChannel } from '@/api/payment'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const channelList = ref([])

// 弹窗
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const editingId = ref(null)

const form = reactive({
  name: '',
  code: '',
  app_id: '',
  mch_id: '',
  api_key: '',
  private_key: '',
  public_key: '',
  notify_url: '',
  sort: 0
})

const rules = {
  name: [{ required: true, message: '请输入渠道名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入渠道代码', trigger: 'blur' }],
  app_id: [{ required: true, message: '请输入APPID', trigger: 'blur' }],
  sort: [{ required: true, message: '请输入排序值', trigger: 'blur' }]
}

const dialogTitle = computed(() => editingId.value ? '编辑支付渠道' : '新增支付渠道')

async function fetchChannels() {
  loading.value = true
  try {
    const res = await getChannels()
    channelList.value = (Array.isArray(res) ? res : (res.list || res.data || [])).map(item => ({
      ...item,
      _toggling: false
    }))
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
    form.code = row.code || ''
    form.app_id = row.app_id || ''
    form.mch_id = row.mch_id || ''
    form.api_key = row.api_key || ''
    form.private_key = row.private_key || ''
    form.public_key = row.public_key || ''
    form.notify_url = row.notify_url || ''
    form.sort = row.sort ?? 0
  } else {
    editingId.value = null
    form.name = ''
    form.code = ''
    form.app_id = ''
    form.mch_id = ''
    form.api_key = ''
    form.private_key = ''
    form.public_key = ''
    form.notify_url = ''
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
    const data = { ...form }
    if (editingId.value) {
      await updateChannel(editingId.value, data)
      ElMessage.success('更新成功')
    } else {
      await createChannel(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchChannels()
  } catch {
    // 错误已在拦截器处理
  } finally {
    saving.value = false
  }
}

async function handleToggleStatus(row, value) {
  row._toggling = true
  try {
    await updateChannel(row.id, { status: value ? 1 : 0 })
    row.status = value ? 1 : 0
    ElMessage.success(value ? '渠道已启用' : '渠道已禁用')
  } catch {
    // 错误已在拦截器处理
  } finally {
    row._toggling = false
  }
}

async function handleDelete(id) {
  try {
    await deleteChannel(id)
    ElMessage.success('删除成功')
    fetchChannels()
  } catch {
    // 错误已在拦截器处理
  }
}

onMounted(fetchChannels)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
