<template>
  <div class="software-page">
    <!-- 搜索栏 -->
    <el-card shadow="hover" class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="软件名称" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="分类">
          <el-tree-select
            v-model="searchForm.category_id"
            :data="categoryTree"
            :props="{ label: 'name', value: 'id', children: 'children' }"
            placeholder="全部分类"
            check-strictly
            clearable
            style="width: 180px"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 130px">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>软件列表</span>
          <el-button type="primary" @click="$router.push('/software/edit')">新增软件</el-button>
        </div>
      </template>

      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="title" label="软件名称" min-width="160" />
        <el-table-column label="分类" width="120">
          <template #default="{ row }">
            {{ row.category?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.type?.name || '-' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="downloads" label="下载量" width="100" align="center" sortable />
        <el-table-column prop="status" label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" sortable />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="$router.push(`/software/edit/${row.id}`)">编辑</el-button>
            <el-popconfirm title="确定要删除该软件吗？" @confirm="handleDelete(row.id)">
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getList, del } from '@/api/software'
import { getTree } from '@/api/category'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const tableData = ref([])
const categoryTree = ref([])

const searchForm = reactive({
  keyword: '',
  category_id: null,
  status: null
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

async function fetchCategories() {
  try {
    const res = await getTree()
    categoryTree.value = Array.isArray(res) ? res : (res.data || [])
  } catch { /* ignore */ }
}

async function fetchList() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.pageSize,
      keyword: searchForm.keyword || undefined,
      category_id: searchForm.category_id || undefined,
      status: searchForm.status ?? undefined
    }
    const res = await getList(params)
    const data = res.data || res
    tableData.value = data.items || data.list || data.data || []
    pagination.total = data.total || 0
  } catch {
    ElMessage.error('加载软件列表失败')
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
  searchForm.category_id = null
  searchForm.status = null
  handleSearch()
}

async function handleDelete(id) {
  try {
    await del(id)
    ElMessage.success('删除成功')
    fetchList()
  } catch { /* error handled in interceptor */ }
}

onMounted(() => {
  fetchCategories()
  fetchList()
})
</script>

<style scoped>
.search-card { margin-bottom: 20px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
</style>
