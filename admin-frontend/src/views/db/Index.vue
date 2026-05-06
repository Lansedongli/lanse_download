<template>
  <div class="db-page">
    <!-- 操作按钮区 -->
    <el-card shadow="hover" class="toolbar-card">
      <div class="toolbar">
        <el-button type="primary" :icon="Plus" :loading="backingUp" @click="handleBackup">
          <el-icon style="margin-right:4px"><Plus /></el-icon>立即备份
        </el-button>
        <el-button :icon="Refresh" @click="handleOptimize" :loading="optimizing">
          <el-icon style="margin-right:4px"><Refresh /></el-icon>优化所有表
        </el-button>
        <el-button :icon="Warning" @click="handleRepair" :loading="repairing">
          <el-icon style="margin-right:4px"><Warning /></el-icon>修复所有表
        </el-button>
        <span class="disk-info">备份目录: /data/backups/sql</span>
      </div>
    </el-card>

    <!-- 备份列表 -->
    <el-card shadow="hover" class="list-card">
      <template #header>
        <div class="page-header">
          <span>备份记录</span>
          <el-button link type="primary" @click="fetchList">刷新列表</el-button>
        </div>
      </template>

      <el-table :data="backupList" border stripe v-loading="loading" empty-text="暂无备份记录">
        <el-table-column prop="batch" label="备份批次" min-width="240">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.batch }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="备份时间" width="180" />
        <el-table-column label="总大小" width="120" align="center">
          <template #default="{ row }">
            {{ formatSize(row.total_size) }}
          </template>
        </el-table-column>
        <el-table-column label="分卷数" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ row.files?.length || 0 }} 个</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" align="center" fixed="right">
          <template #default="{ row }">
            <el-button link type="success" size="small" @click="handleRestore(row)">
              恢复
            </el-button>
            <el-popconfirm
              title="确定要删除该备份批次的所有文件吗？"
              confirm-button-text="确定删除"
              cancel-button-text="取消"
              @confirm="handleDelete(row)"
            >
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
            <!-- 展开查看分卷 -->
            <el-button link type="info" size="small" @click="toggleExpand(row)">
              {{ expandedBatches.has(row.batch) ? '收起' : '详情' }}
            </el-button>
          </template>
        </el-table-column>

        <!-- 分卷展开 -->
        <template #expanded-row>
          <template v-for="batch in backupList" :key="batch.batch">
            <tr v-if="expandedBatches.has(batch.batch)">
              <td :colspan="5" style="padding: 12px 20px; background: #fafafa;">
                <el-table :data="batch.files" border size="small" style="width: 100%">
                  <el-table-column prop="filename" label="文件名" min-width="280" />
                  <el-table-column label="大小" width="120" align="center">
                    <template #default="{ row: f }">{{ formatSize(f.size) }}</template>
                  </el-table-column>
                  <el-table-column label="操作" width="120" align="center">
                    <template #default="{ row: f }">
                      <el-button link type="success" size="small" @click="handleRestoreFile(f.filename)">
                        恢复此卷
                      </el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </td>
            </tr>
          </template>
        </template>
      </el-table>
    </el-card>

    <!-- 恢复确认弹窗 -->
    <el-dialog v-model="restoreDialogVisible" title="确认恢复" width="420px" :close-on-click-modal="false">
      <el-alert
        title="⚠️ 恢复操作将覆盖当前数据库，请确认已做好备份！"
        type="warning"
        :closable="false"
        show-icon
        style="margin-bottom: 16px"
      />
      <p>即将从备份文件恢复：<el-tag>{{ restoreTarget }}</el-tag></p>
      <template #footer>
        <el-button @click="restoreDialogVisible = false">取消</el-button>
        <el-button type="danger" :loading="restoring" @click="doRestore">确认恢复</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getBackupList, createBackup, restoreBackup, deleteBackup, optimizeTables, repairTables } from '@/api/db'
import { ElMessage } from 'element-plus'
import { Plus, Refresh, Warning } from '@element-plus/icons-vue'

const loading = ref(false)
const backupList = ref([])
const backingUp = ref(false)
const optimizing = ref(false)
const repairing = ref(false)
const restoring = ref(false)

// 展开详情
const expandedBatches = ref(new Set())
function toggleExpand(row) {
  if (expandedBatches.value.has(row.batch)) {
    expandedBatches.value.delete(row.batch)
  } else {
    expandedBatches.value.add(row.batch)
  }
  expandedBatches.value = new Set(expandedBatches.value)
}

function formatSize(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0
  let size = bytes
  while (size >= 1024 && i < units.length - 1) { size /= 1024; i++ }
  return size.toFixed(2) + ' ' + units[i]
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getBackupList()
    backupList.value = Array.isArray(res) ? res : (res.list || [])
  } catch { /* 拦截器处理 */ } finally { loading.value = false }
}

async function handleBackup() {
  backingUp.value = true
  try {
    await createBackup()
    ElMessage.success('全量备份成功！')
    fetchList()
  } catch { /* 拦截器处理 */ } finally { backingUp.value = false }
}

async function handleOptimize() {
  optimizing.value = true
  try {
    const res = await optimizeTables()
    ElMessage.success(res?.message || '优化完成')
  } catch { /* 拦截器处理 */ } finally { optimizing.value = false }
}

async function handleRepair() {
  repairing.value = true
  try {
    const res = await repairTables()
    ElMessage.success(res?.message || '修复完成')
  } catch { /* 拦截器处理 */ } finally { repairing.value = false }
}

// 恢复
const restoreDialogVisible = ref(false)
const restoreTarget = ref('')

function handleRestore(row) {
  // 恢复整批：取第一个文件
  const file = row.files?.[0]
  if (!file) return ElMessage.warning('该批次没有备份文件')
  restoreTarget.value = file.filename
  restoreDialogVisible.value = true
}

function handleRestoreFile(filename) {
  restoreTarget.value = filename
  restoreDialogVisible.value = true
}

async function doRestore() {
  restoring.value = true
  try {
    await restoreBackup(restoreTarget.value)
    ElMessage.success('数据库恢复成功！')
    restoreDialogVisible.value = false
    fetchList()
  } catch { /* 拦截器处理 */ } finally { restoring.value = false }
}

async function handleDelete(row) {
  // 删除整批：逐个删除
  try {
    for (const file of row.files || []) {
      await deleteBackup(file.id)
    }
    ElMessage.success('备份批次已删除')
    fetchList()
  } catch { /* 拦截器处理 */ }
}

onMounted(fetchList)
</script>

<style scoped>
.toolbar-card { margin-bottom: 16px; }
.toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.disk-info { margin-left: auto; color: #909399; font-size: 13px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
