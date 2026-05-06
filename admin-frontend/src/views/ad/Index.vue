<template>
  <div class="ad-page">
    <el-card shadow="hover">
      <el-empty description="广告管理模块开发中">
        <template #image>
          <el-icon :size="80" color="#909399"><Promotion /></el-icon>
        </template>
        <el-text type="info" size="large">该功能即将上线，敬请期待</el-text>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
// 广告管理 - 占位页面
</script>

<style scoped>
.ad-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; }
</style>
