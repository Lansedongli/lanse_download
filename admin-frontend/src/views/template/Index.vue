<template>
  <div class="template-page">
    <el-card shadow="hover">
      <el-empty description="模板变量模块开发中">
        <template #image>
          <el-icon :size="80" color="#909399"><EditPen /></el-icon>
        </template>
        <el-text type="info" size="large">该功能即将上线，敬请期待</el-text>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
// 模板变量 - 占位页面
</script>

<style scoped>
.template-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; }
</style>
