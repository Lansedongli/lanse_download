<template>
  <div class="category-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>分类管理</span>
          <el-button type="primary" @click="openDialog(null)">新增分类</el-button>
        </div>
      </template>

      <el-table
        :data="categoryList"
        row-key="id"
        border
        stripe
        default-expand-all
        v-loading="loading"
      >
        <el-table-column prop="name" label="分类名称" min-width="180" />
        <el-table-column prop="code" label="分类编码" width="140" />
        <el-table-column prop="sort" label="排序" width="80" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openDialog(row)">编辑</el-button>
            <el-button
              link
              type="primary"
              size="small"
              @click="openDialog(null, row.id)"
            >添加子分类</el-button>
            <el-popconfirm title="确定要删除该分类及其子分类吗？" @confirm="handleDelete(row.id)">
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
      width="520px"
      :close-on-click-modal="false"
      @closed="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="上级分类" prop="parent_id">
          <el-tree-select
            v-model="form.parent_id"
            :data="treeSelectData"
            :props="{ label: 'name', value: 'id', children: 'children' }"
            placeholder="请选择上级分类（留空为顶级）"
            check-strictly
            clearable
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="分类名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="分类编码" prop="code">
          <el-input v-model="form.code" placeholder="请输入分类编码" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" placeholder="排序值" style="width: 100%" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
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
import { getTree, create, update, del } from '@/api/category'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const categoryList = ref([])
const treeSelectData = ref([])

// 弹窗
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const editingId = ref(null)
const defaultParentId = ref(null)

const form = reactive({
  parent_id: null,
  name: '',
  code: '',
  sort: 0,
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入分类编码', trigger: 'blur' }]
}

const dialogTitle = computed(() => editingId.value ? '编辑分类' : '新增分类')

// 扁平化树为表格数据（带缩进）
function flattenTree(tree, level = 0) {
  const result = []
  tree.forEach(item => {
    result.push({
      ...item,
      name: '　'.repeat(level) + item.name,
      _level: level
    })
    if (item.children && item.children.length) {
      result.push(...flattenTree(item.children, level + 1))
    }
  })
  return result
}

// 树形选择器数据（保留原始 name 不做缩进）
function cloneTree(tree) {
  return tree.map(item => ({
    ...item,
    children: item.children ? cloneTree(item.children) : undefined
  }))
}

async function fetchCategories() {
  loading.value = true
  try {
    const res = await getTree()
    const rawTree = Array.isArray(res) ? res : (res.data || [])
    treeSelectData.value = cloneTree(rawTree)
    categoryList.value = flattenTree(rawTree)
  } catch {
    ElMessage.error('加载分类失败')
  } finally {
    loading.value = false
  }
}

function openDialog(row, parentId = null) {
  defaultParentId.value = parentId
  if (row) {
    editingId.value = row.id
    form.parent_id = row.parent_id || null
    form.name = row.name.replace(/^\　+/, '')
    form.code = row.code
    form.sort = row.sort
    form.status = row.status
  } else {
    editingId.value = null
    form.parent_id = parentId
    form.name = ''
    form.code = ''
    form.sort = 0
    form.status = 1
  }
  dialogVisible.value = true
}

function resetForm() {
  formRef.value?.resetFields()
  form.parent_id = null
  form.name = ''
  form.code = ''
  form.sort = 0
  form.status = 1
  editingId.value = null
  defaultParentId.value = null
}

async function handleSave() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    const data = { ...form }
    if (editingId.value) {
      await update(editingId.value, data)
      ElMessage.success('更新成功')
    } else {
      await create(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchCategories()
  } catch {
    // 错误已在拦截器处理
  } finally {
    saving.value = false
  }
}

async function handleDelete(id) {
  try {
    await del(id)
    ElMessage.success('删除成功')
    fetchCategories()
  } catch {
    // 错误已在拦截器处理
  }
}

onMounted(fetchCategories)
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
