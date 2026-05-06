<template>
  <div class="software-edit-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>{{ isEdit ? '编辑软件' : '新增软件' }}</span>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
        class="edit-form"
        v-loading="pageLoading"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="软件名称" prop="title">
              <el-input v-model="form.title" placeholder="请输入软件名称" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="副标题" prop="subtitle">
              <el-input v-model="form.subtitle" placeholder="请输入副标题" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="软件编码" prop="code">
              <el-input v-model="form.code" placeholder="请输入唯一编码" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所属分类" prop="category_id">
              <el-tree-select
                v-model="form.category_id"
                :data="categoryTree"
                :props="{ label: 'name', value: 'id', children: 'children' }"
                placeholder="请选择分类"
                check-strictly
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="软件类型" prop="type_id">
              <el-select v-model="form.type_id" placeholder="请选择类型" style="width: 100%">
                <el-option label="本地下载" :value="1" />
                <el-option label="网盘下载" :value="2" />
                <el-option label="外链跳转" :value="3" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="授权方式" prop="license">
              <el-select v-model="form.license" placeholder="请选择授权方式" style="width: 100%">
                <el-option label="免费" value="free" />
                <el-option label="共享" value="share" />
                <el-option label="商业" value="commercial" />
                <el-option label="开源" value="opensource" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="运行平台" prop="platform">
              <el-select v-model="form.platform" placeholder="请选择平台" style="width: 100%">
                <el-option label="Windows" value="windows" />
                <el-option label="macOS" value="macos" />
                <el-option label="Linux" value="linux" />
                <el-option label="Android" value="android" />
                <el-option label="iOS" value="ios" />
                <el-option label="Web" value="web" />
                <el-option label="跨平台" value="cross" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="版本号" prop="version">
              <el-input v-model="form.version" placeholder="如 1.0.0" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="作者" prop="author">
              <el-input v-model="form.author" placeholder="请输入作者名称" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所需积分" prop="require_points">
              <el-input-number v-model="form.require_points" :min="0" :max="99999" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="软件描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="6"
            placeholder="请输入软件描述"
          />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
              <span class="switch-label">{{ form.status === 1 ? '启用' : '禁用' }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="推荐" prop="is_recommend">
              <el-switch v-model="form.is_recommend" :active-value="1" :inactive-value="0" />
              <span class="switch-label">{{ form.is_recommend === 1 ? '推荐' : '不推荐' }}</span>
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">
            {{ saving ? '保存中...' : '保存' }}
          </el-button>
          <el-button @click="$router.back()">返回</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getDetail, create, update } from '@/api/software'
import { getTree } from '@/api/category'
import { ElMessage } from 'element-plus'

const route = useRoute()
const router = useRouter()

const formRef = ref(null)
const saving = ref(false)
const pageLoading = ref(false)
const categoryTree = ref([])

const isEdit = computed(() => !!route.params.id)

const form = reactive({
  title: '',
  subtitle: '',
  code: '',
  category_id: null,
  type_id: null,
  license: '',
  platform: '',
  version: '',
  author: '',
  require_points: 0,
  description: '',
  status: 1,
  is_recommend: 0
})

const rules = {
  title: [{ required: true, message: '请输入软件名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入软件编码', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
  type_id: [{ required: true, message: '请选择类型', trigger: 'change' }]
}

async function fetchCategories() {
  try {
    const res = await getTree()
    categoryTree.value = Array.isArray(res) ? res : (res.data || [])
  } catch { /* ignore */ }
}

async function fetchDetail() {
  if (!isEdit.value) return
  pageLoading.value = true
  try {
    const res = await getDetail(route.params.id)
    const data = res.data || res
    Object.assign(form, {
      title: data.title ?? '',
      subtitle: data.subtitle ?? '',
      code: data.code ?? '',
      category_id: data.category_id ?? null,
      type_id: data.type_id ?? null,
      license: data.license ?? '',
      platform: data.platform ?? '',
      version: data.version ?? '',
      author: data.author ?? '',
      require_points: data.require_points ?? 0,
      description: data.description ?? '',
      status: data.status ?? 1,
      is_recommend: data.is_recommend ?? 0
    })
  } catch {
    ElMessage.error('加载软件信息失败')
    router.back()
  } finally {
    pageLoading.value = false
  }
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    const data = { ...form }
    if (isEdit.value) {
      await update(route.params.id, data)
      ElMessage.success('更新成功')
    } else {
      await create(data)
      ElMessage.success('创建成功')
    }
    router.push('/software')
  } catch {
    // error handled in interceptor
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchCategories()
  fetchDetail()
})
</script>

<style scoped>
.edit-form { max-width: 900px; }
.switch-label { margin-left: 8px; color: #909399; font-size: 13px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
