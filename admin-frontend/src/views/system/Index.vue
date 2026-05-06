<template>
  <div class="system-page">
    <el-card shadow="hover">
      <template #header>
        <div class="page-header">
          <span>系统配置</span>
        </div>
      </template>

      <el-form :model="configForm" label-width="120px" style="max-width: 600px">
        <el-form-item label="站点名称">
          <el-input v-model="configForm.site_name" placeholder="请输入站点名称" />
        </el-form-item>
        <el-form-item label="站点关键词">
          <el-input v-model="configForm.site_keywords" placeholder="请输入站点关键词，多个用逗号分隔" />
        </el-form-item>
        <el-form-item label="版权信息">
          <el-input v-model="configForm.copyright" type="textarea" :rows="3" placeholder="请输入版权信息" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'

const saving = ref(false)

const configForm = reactive({
  site_name: '帝国下载系统',
  site_keywords: '软件下载,资源分享,帝国下载',
  copyright: '© 2024 帝国下载系统 All Rights Reserved.'
})

async function handleSave() {
  saving.value = true
  // Mock save — 模拟保存操作
  await new Promise(resolve => setTimeout(resolve, 800))
  ElMessage.success('配置保存成功')
  saving.value = false
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; }
</style>
