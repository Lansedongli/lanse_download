<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSoftwareDetail } from '@/api/software'
import { getComments, postComment } from '@/api/user'
import { getDownloadUrl } from '@/api/user'
import { addFavorite, removeFavorite, getFavorites } from '@/api/user'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const softwareId = Number(route.params.id)

const software = ref(null)
const loading = ref(false)
const error = ref('')

// 评论
const comments = ref([])
const commentsLoading = ref(false)
const commentPage = ref(1)
const commentContent = ref('')
const commentRating = ref(5)
const submittingComment = ref(false)

// 收藏
const isFavorited = ref(false)
const favoriteLoading = ref(false)

// 下载
const downloading = ref(false)

onMounted(async () => {
  await fetchDetail()
  await fetchComments()
  if (authStore.isLoggedIn) {
    await checkFavorite()
  }
})

async function fetchDetail() {
  loading.value = true
  error.value = ''
  try {
    const res = await getSoftwareDetail(softwareId)
    software.value = res.software || res.data || res
  } catch (e) {
    error.value = '加载软件详情失败'
  } finally {
    loading.value = false
  }
}

async function fetchComments() {
  commentsLoading.value = true
  try {
    const res = await getComments(softwareId, commentPage.value)
    const list = res.comments || res.data?.comments || res.data || res || []
    comments.value = Array.isArray(list) ? list : []
  } catch (e) {
    comments.value = []
  } finally {
    commentsLoading.value = false
  }
}

async function checkFavorite() {
  try {
    const res = await getFavorites(1)
    const list = res.favorites || res.data?.favorites || res.data || res || []
    const favList = Array.isArray(list) ? list : []
    isFavorited.value = favList.some(
      (f) => (f.software_id === softwareId) || (f.id === softwareId) || (f.software?.id === softwareId)
    )
  } catch (e) {
    // ignore
  }
}

async function handleDownload() {
  if (!authStore.isLoggedIn) {
    ElMessageBox.confirm('下载需要登录，是否前往登录？', '提示', {
      confirmButtonText: '去登录',
      cancelButtonText: '取消',
      type: 'warning',
    }).then(() => {
      router.push({ name: 'Login', query: { redirect: route.fullPath } })
    }).catch(() => {})
    return
  }

  downloading.value = true
  try {
    const res = await getDownloadUrl(softwareId)
    const url = res.url || res.download_url || res.data?.url
    const token = res.token || res.data?.token
    if (url) {
      window.open(url, '_blank')
    } else if (token) {
      window.open(`/api/v1/download/file?token=${token}`, '_blank')
    }
    ElMessage.success('开始下载')
  } catch (e) {
    // 错误已在拦截器中处理
  } finally {
    downloading.value = false
  }
}

async function handleFavorite() {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'Login', query: { redirect: route.fullPath } })
    return
  }

  favoriteLoading.value = true
  try {
    if (isFavorited.value) {
      await removeFavorite(softwareId)
      isFavorited.value = false
      ElMessage.success('已取消收藏')
    } else {
      await addFavorite(softwareId)
      isFavorited.value = true
      ElMessage.success('已添加收藏')
    }
  } catch (e) {
    // ignore
  } finally {
    favoriteLoading.value = false
  }
}

async function handleComment() {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'Login', query: { redirect: route.fullPath } })
    return
  }

  const content = commentContent.value.trim()
  if (!content) {
    ElMessage.warning('请输入评论内容')
    return
  }

  submittingComment.value = true
  try {
    await postComment({
      software_id: softwareId,
      content,
      rating: commentRating.value,
    })
    ElMessage.success('评论发表成功')
    commentContent.value = ''
    commentRating.value = 5
    commentPage.value = 1
    await fetchComments()
  } catch (e) {
    // ignore
  } finally {
    submittingComment.value = false
  }
}
</script>

<template>
  <div class="detail-page" v-loading="loading">
    <!-- 错误状态 -->
    <el-result v-if="error" icon="error" title="加载失败" :sub-title="error">
      <template #extra>
        <el-button type="primary" @click="fetchDetail">重试</el-button>
      </template>
    </el-result>

    <template v-else-if="software">
      <!-- 软件信息 -->
      <div class="detail-header">
        <div class="detail-icon">
          <img
            :src="software.icon || software.image_url || 'https://picsum.photos/200/200'"
            :alt="software.name"
          />
        </div>
        <div class="detail-info">
          <h1 class="detail-name">{{ software.name }}</h1>
          <p class="detail-version" v-if="software.version">
            版本：{{ software.version }}
          </p>
          <div class="detail-meta">
            <span v-if="software.category">分类：{{ software.category?.name || software.category }}</span>
            <span v-if="software.downloads">📥 {{ software.downloads }} 次下载</span>
            <span v-if="software.rating">⭐ {{ software.rating }} 分</span>
            <span v-if="software.points !== undefined">
              {{ software.points === 0 ? '免费' : '💎 ' + software.points + ' 积分' }}
            </span>
          </div>
          <div class="detail-actions">
            <el-button
              type="primary"
              size="large"
              :loading="downloading"
              @click="handleDownload"
            >
              {{ software.points === 0 ? '免费下载' : '下载 (' + software.points + ' 积分)' }}
            </el-button>
            <el-button
              :type="isFavorited ? 'warning' : 'default'"
              size="large"
              :loading="favoriteLoading"
              :icon="isFavorited ? 'StarFilled' : 'Star'"
              @click="handleFavorite"
            >
              {{ isFavorited ? '已收藏' : '收藏' }}
            </el-button>
          </div>
        </div>
      </div>

      <!-- 简介 -->
      <section class="detail-section">
        <h3>软件介绍</h3>
        <p class="detail-desc">{{ software.description || software.summary || '暂无介绍' }}</p>
      </section>

      <!-- 截图 -->
      <section class="detail-section" v-if="software.screenshots && software.screenshots.length > 0">
        <h3>软件截图</h3>
        <div class="screenshots-grid">
          <img
            v-for="(img, idx) in software.screenshots"
            :key="idx"
            :src="typeof img === 'string' ? img : img.url"
            class="screenshot-img"
            @click="() => {}"
          />
        </div>
      </section>

      <!-- 评论 -->
      <section class="detail-section">
        <h3>用户评论 ({{ comments.length }})</h3>

        <!-- 发表评论 -->
        <div class="comment-form" v-if="authStore.isLoggedIn">
          <div class="comment-rating">
            <span>评分：</span>
            <el-rate v-model="commentRating" show-score />
          </div>
          <el-input
            v-model="commentContent"
            type="textarea"
            :rows="3"
            placeholder="写下你的评论..."
            maxlength="500"
            show-word-limit
          />
          <el-button
            type="primary"
            :loading="submittingComment"
            @click="handleComment"
            style="margin-top: 10px"
          >
            发表评论
          </el-button>
        </div>
        <div class="comment-login-tip" v-else>
          <el-button text type="primary" @click="router.push({ name: 'Login', query: { redirect: route.fullPath } })">
            登录后发表评论
          </el-button>
        </div>

        <!-- 评论列表 -->
        <div v-loading="commentsLoading" class="comment-list">
          <el-empty v-if="!commentsLoading && comments.length === 0" description="暂无评论" />
          <div v-for="item in comments" :key="item.id" class="comment-item">
            <div class="comment-header">
              <el-avatar :size="32" icon="UserFilled" />
              <div class="comment-user-info">
                <span class="comment-username">{{ item.user?.username || item.username || '用户' }}</span>
                <span class="comment-time">{{ item.created_at || item.create_time || '' }}</span>
              </div>
              <el-rate
                v-model="item.rating"
                disabled
                show-score
                size="small"
                v-if="item.rating"
              />
            </div>
            <p class="comment-content">{{ item.content }}</p>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.detail-page {
  padding-bottom: 40px;
}

/* 头部 */
.detail-header {
  display: flex;
  gap: 24px;
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
}

.detail-icon {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.detail-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.detail-info {
  flex: 1;
}

.detail-name {
  font-size: 24px;
  font-weight: 700;
  color: #303133;
  margin: 0 0 8px;
}

.detail-version {
  color: #909399;
  font-size: 14px;
  margin: 0 0 8px;
}

.detail-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  font-size: 14px;
  color: #606266;
  margin-bottom: 16px;
}

.detail-actions {
  display: flex;
  gap: 12px;
}

/* 区块 */
.detail-section {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
}

.detail-section h3 {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #409eff;
}

.detail-desc {
  font-size: 15px;
  line-height: 1.8;
  color: #606266;
  white-space: pre-wrap;
}

/* 截图 */
.screenshots-grid {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.screenshot-img {
  height: 200px;
  border-radius: 8px;
  cursor: pointer;
  border: 1px solid #ebeef5;
}

/* 评论 */
.comment-form {
  margin-bottom: 20px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.comment-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  font-size: 14px;
  color: #606266;
}

.comment-login-tip {
  padding: 16px;
  text-align: center;
  margin-bottom: 20px;
}

.comment-item {
  padding: 16px 0;
  border-bottom: 1px solid #f2f2f2;
}

.comment-item:last-child {
  border-bottom: none;
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.comment-user-info {
  display: flex;
  flex-direction: column;
}

.comment-username {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}

.comment-time {
  font-size: 12px;
  color: #c0c4cc;
}

.comment-content {
  font-size: 14px;
  color: #606266;
  line-height: 1.6;
  margin: 0;
  padding-left: 42px;
}

/* 响应式 */
@media (max-width: 768px) {
  .detail-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .detail-meta {
    justify-content: center;
  }

  .detail-actions {
    justify-content: center;
    flex-wrap: wrap;
  }

  .detail-section {
    padding: 16px;
  }
}
</style>
