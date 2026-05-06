<template>
  <div class="static-page">
    <el-card shadow="hover" class="status-card">
      <template #header>
        <div class="page-header">
          <span>静态化状态</span>
          <el-tag :type="status === 'running' ? 'warning' : 'success'">
            {{ status === 'running' ? '生成中...' : status === 'idle' ? '空闲' : status }}
          </el-tag>
        </div>
      </template>

      <div class="status-grid" v-if="taskInfo">
        <el-descriptions :column="3" border size="small">
          <el-descriptions-item label="当前任务">{{ taskInfo.task || '-' }}</el-descriptions-item>
          <el-descriptions-item label="进度">{{ taskInfo.progress || '0%' }}</el-descriptions-item>
          <el-descriptions-item label="耗时">{{ taskInfo.elapsed || '-' }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-card>

    <el-card shadow="hover" class="generate-card">
      <template #header><span>手动生成静态页</span></template>

      <el-row :gutter="20">
        <!-- 生成首页 -->
        <el-col :span="8">
          <el-card shadow="never" class="type-card">
            <div class="type-icon"><el-icon :size="40"><HomeFilled /></el-icon></div>
            <h3>首页</h3>
            <p>重新生成网站首页静态文件</p>
            <el-button type="primary" :loading="generating === 'homepage'" @click="generate('homepage')" style="width:100%">
              {{ generating === 'homepage' ? '生成中...' : '生成首页' }}
            </el-button>
          </el-card>
        </el-col>

        <!-- 生成分类页 -->
        <el-col :span="8">
          <el-card shadow="never" class="type-card">
            <div class="type-icon"><el-icon :size="40"><Grid /></el-icon></div>
            <h3>分类页</h3>
            <p>生成软件分类列表及详情页</p>
            <el-form inline>
              <el-form-item label="分类ID">
                <el-input-number v-model="categoryId" :min="1" placeholder="留空生成全部" style="width:130px" />
              </el-form-item>
            </el-form>
            <el-button type="primary" :loading="generating === 'category'" @click="generate('category')" style="width:100%">
              {{ generating === 'category' ? '生成中...' : '生成分类页' }}
            </el-button>
          </el-card>
        </el-col>

        <!-- 生成详情页 -->
        <el-col :span="8">
          <el-card shadow="never" class="type-card">
            <div class="type-icon"><el-icon :size="40"><Document /></el-icon></div>
            <h3>详情页</h3>
            <p>重新生成软件详情静态页面</p>
            <el-form inline>
              <el-form-item label="软件ID">
                <el-input-number v-model="softwareId" :min="1" placeholder="留空生成全部" style="width:130px" />
              </el-form-item>
            </el-form>
            <el-button type="primary" :loading="generating === 'detail'" @click="generate('detail')" style="width:100%">
              {{ generating === 'detail' ? '生成中...' : '生成详情页' }}
            </el-button>
          </el-card>
        </el-col>
      </el-row>

      <!-- 一键全站 -->
      <el-divider />
      <div style="text-align: center">
        <el-button type="success" size="large" :loading="generating === 'batch'" @click="generate('batch')">
          <el-icon style="margin-right:6px"><MagicStick /></el-icon>
          一键生成全站静态 {{ generating === 'batch' ? '...' : '' }}
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { getStaticStatus, generateStatic } from '@/api/static'
import { ElMessage } from 'element-plus'
import { HomeFilled, Grid, Document, MagicStick } from '@element-plus/icons-vue'

const status = ref('idle')
const taskInfo = ref(null)
const generating = ref(null)

const categoryId = ref(null)
const softwareId = ref(null)

let statusTimer = null

async function fetchStatus() {
  try {
    const res = await getStaticStatus()
    status.value = res.status || 'idle'
    taskInfo.value = res
  } catch { /* 拦截器处理 */ }
}

async function generate(type) {
  if (generating.value) return

  generating.value = type
  try {
    let id = null
    if (type === 'category') id = categoryId.value
    if (type === 'detail') id = softwareId.value

    await generateStatic(type, id)
    ElMessage.success('静态生成任务已提交！')
    fetchStatus()
  } catch { /* 拦截器处理 */ } finally {
    generating.value = null
  }
}

onMounted(() => {
  fetchStatus()
  statusTimer = setInterval(fetchStatus, 5000)
})

onUnmounted(() => {
  clearInterval(statusTimer)
})
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
.status-card { margin-bottom: 16px; }
.generate-card { margin-top: 0; }
.type-card { text-align: center; height: 100%; }
.type-card h3 { margin: 12px 0 8px; font-size: 18px; }
.type-card p { color: #909399; font-size: 13px; margin-bottom: 16px; min-height: 40px; }
.type-icon { color: #409EFF; margin-top: 12px; }
</style>
