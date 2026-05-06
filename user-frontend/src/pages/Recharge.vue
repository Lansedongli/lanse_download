<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPackages, getChannels, createPayment, queryPayment } from '@/api/payment'

const router = useRouter()

// ==================== 步骤控制 ====================
const step = ref(1) // 1-选择套餐 2-选择支付方式 3-确认支付

// ==================== 步骤1：选择套餐 ====================
const packages = ref([])
const packagesLoading = ref(false)
const selectedPackage = ref(null)

async function fetchPackages() {
  packagesLoading.value = true
  try {
    const res = await getPackages()
    packages.value = Array.isArray(res) ? res : (res.data || res.packages || [])
  } catch {
    // 模拟数据
    packages.value = [
      { id: 1, name: '青铜套餐', amount: 10, points: 100, bonus: 0, hot: false },
      { id: 2, name: '白银套餐', amount: 30, points: 350, bonus: 50, hot: false },
      { id: 3, name: '黄金套餐', amount: 50, points: 600, bonus: 100, hot: true },
      { id: 4, name: '铂金套餐', amount: 100, points: 1300, bonus: 300, hot: false },
      { id: 5, name: '钻石套餐', amount: 200, points: 2800, bonus: 800, hot: true },
      { id: 6, name: '至尊套餐', amount: 500, points: 7500, bonus: 2500, hot: false },
      { id: 7, name: '体验套餐', amount: 5, points: 45, bonus: 5, hot: false },
      { id: 8, name: '超值月卡', amount: 68, points: 800, bonus: 200, hot: false },
    ]
  } finally {
    packagesLoading.value = false
  }
}

function selectPackage(pkg) {
  selectedPackage.value = pkg
}

function goToStep2() {
  if (!selectedPackage.value) {
    ElMessage.warning('请先选择一个充值套餐')
    return
  }
  step.value = 2
}

// ==================== 步骤2：选择支付方式 ====================
const channels = ref([])
const channelsLoading = ref(false)
const selectedChannel = ref(null)

const channelIcons = {
  alipay: '🏦',
  wechat: '💬',
}

async function fetchChannels() {
  channelsLoading.value = true
  try {
    const res = await getChannels()
    channels.value = Array.isArray(res) ? res : (res.data || res.channels || [])
  } catch {
    channels.value = [
      { code: 'alipay', name: '支付宝', icon: '🏦' },
      { code: 'wechat', name: '微信支付', icon: '💬' },
    ]
  } finally {
    channelsLoading.value = false
  }
}

function selectChannel(channel) {
  selectedChannel.value = channel
}

function goToStep3() {
  if (!selectedChannel.value) {
    ElMessage.warning('请选择支付方式')
    return
  }
  step.value = 3
}

// ==================== 步骤3：确认支付 ====================
const paying = ref(false)
const orderNo = ref('')
const payHtml = ref('')   // 支付宝 HTML form
const qrCodeUrl = ref('') // 微信二维码
const payDialogVisible = ref(false)
const payStatus = ref('') // pending / success / failed
const pollingTimer = ref(null)

// 订单摘要
const orderSummary = computed(() => {
  if (!selectedPackage.value) return {}
  return {
    name: selectedPackage.value.name,
    amount: selectedPackage.value.amount,
    points: selectedPackage.value.points + (selectedPackage.value.bonus || 0),
    bonus: selectedPackage.value.bonus || 0,
    channel: selectedChannel.value?.name || '',
  }
})

async function handlePay() {
  paying.value = true
  try {
    const res = await createPayment({
      package_id: selectedPackage.value.id,
      channel: selectedChannel.value.code,
    })

    const data = res.data || res
    orderNo.value = data.order_no || data.orderNo || ''

    if (selectedChannel.value.code === 'alipay') {
      // 支付宝返回 HTML form
      payHtml.value = data.pay_form || data.form || data.html || ''
      payDialogVisible.value = true
    } else if (selectedChannel.value.code === 'wechat') {
      // 微信返回二维码
      qrCodeUrl.value = data.qr_code || data.qrcode || data.code_url || ''
      payDialogVisible.value = true
    }

    payStatus.value = 'pending'
    // 开始轮询支付状态
    startPolling()
  } catch {
    ElMessage.error('创建支付订单失败，请稍后重试')
  } finally {
    paying.value = false
  }
}

function startPolling() {
  stopPolling()
  pollingTimer.value = setInterval(async () => {
    if (!orderNo.value) return
    try {
      const res = await queryPayment(orderNo.value)
      const data = res.data || res
      const status = data.status || data.pay_status || ''
      if (status === 'success' || status === 'paid' || status === 1) {
        stopPolling()
        payStatus.value = 'success'
        ElMessage.success('支付成功！')
        setTimeout(() => {
          payDialogVisible.value = false
          router.push({ name: 'UserRecharge' })
        }, 2000)
      } else if (status === 'failed' || status === 'closed' || status === -1) {
        stopPolling()
        payStatus.value = 'failed'
        ElMessage.error('支付失败，请重试')
      }
    } catch {
      // 轮询失败忽略
    }
  }, 3000)
}

function stopPolling() {
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
    pollingTimer.value = null
  }
}

function retryPay() {
  payDialogVisible.value = false
  payHtml.value = ''
  qrCodeUrl.value = ''
  payStatus.value = ''
  orderNo.value = ''
  stopPolling()
  step.value = 2
}

function goBackToStep1() {
  step.value = 1
}

function goBackToStep2() {
  step.value = 2
}

// ==================== 生命周期 ====================
onMounted(() => {
  fetchPackages()
  fetchChannels()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <div class="recharge-online-page">
    <div class="page-header">
      <h2>在线充值</h2>
      <p class="page-desc">选择套餐，轻松充值，点数即时到账</p>
    </div>

    <!-- 步骤条 -->
    <el-steps :active="step" align-center class="recharge-steps" finish-status="success">
      <el-step title="选择套餐" description="选择适合您的充值套餐" />
      <el-step title="支付方式" description="选择支付宝或微信支付" />
      <el-step title="确认支付" description="确认订单并完成支付" />
    </el-steps>

    <!-- ==================== 步骤1：选择套餐 ==================== -->
    <div v-show="step === 1" class="step-content">
      <div v-loading="packagesLoading" class="packages-grid">
        <div
          v-for="pkg in packages"
          :key="pkg.id"
          class="package-card"
          :class="{ active: selectedPackage?.id === pkg.id }"
          @click="selectPackage(pkg)"
        >
          <div v-if="pkg.hot" class="hot-badge">
            <el-tag type="danger" size="small" effect="dark">🔥 热推</el-tag>
          </div>
          <div class="package-name">{{ pkg.name }}</div>
          <div class="package-amount">
            <span class="currency">¥</span>
            <span class="amount-num">{{ pkg.amount }}</span>
          </div>
          <div class="package-info">
            <span class="info-label">获得点数</span>
            <span class="info-value points-value">{{ pkg.points }}</span>
          </div>
          <div v-if="pkg.bonus > 0" class="package-bonus">
            <el-tag type="warning" size="small" effect="plain">
              赠送 {{ pkg.bonus }} 点
            </el-tag>
          </div>
          <div v-else class="package-bonus no-bonus">
            <span class="no-bonus-text">无赠送</span>
          </div>
        </div>
      </div>

      <div class="step-actions">
        <el-button type="primary" size="large" @click="goToStep2" :disabled="!selectedPackage">
          下一步：选择支付方式
        </el-button>
      </div>
    </div>

    <!-- ==================== 步骤2：选择支付方式 ==================== -->
    <div v-show="step === 2" class="step-content">
      <h3 class="step-title">选择支付方式</h3>
      <div v-loading="channelsLoading" class="channels-grid">
        <div
          v-for="channel in channels"
          :key="channel.code"
          class="channel-card"
          :class="{ active: selectedChannel?.code === channel.code }"
          @click="selectChannel(channel)"
        >
          <div class="channel-icon">{{ channelIcons[channel.code] || '💳' }}</div>
          <div class="channel-name">{{ channel.name }}</div>
          <div class="channel-desc">
            {{ channel.code === 'alipay' ? '推荐支付宝用户使用' : '推荐微信用户使用' }}
          </div>
        </div>
      </div>

      <div class="step-actions">
        <el-button size="large" @click="goBackToStep1">上一步</el-button>
        <el-button type="primary" size="large" @click="goToStep3" :disabled="!selectedChannel">
          下一步：确认支付
        </el-button>
      </div>
    </div>

    <!-- ==================== 步骤3：确认支付 ==================== -->
    <div v-show="step === 3" class="step-content">
      <h3 class="step-title">确认订单</h3>
      <el-card shadow="never" class="order-summary-card">
        <div class="summary-item">
          <span class="summary-label">套餐名称</span>
          <span class="summary-value">{{ orderSummary.name }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">支付金额</span>
          <span class="summary-value amount-highlight">¥{{ orderSummary.amount }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">获得点数</span>
          <span class="summary-value points-highlight">{{ orderSummary.points }} 点</span>
        </div>
        <div v-if="orderSummary.bonus > 0" class="summary-item">
          <span class="summary-label">赠送点数</span>
          <span class="summary-value bonus-highlight">+{{ orderSummary.bonus }} 点</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">支付方式</span>
          <span class="summary-value">{{ orderSummary.channel }}</span>
        </div>
      </el-card>

      <div class="step-actions">
        <el-button size="large" @click="goBackToStep2">上一步</el-button>
        <el-button
          type="primary"
          size="large"
          :loading="paying"
          @click="handlePay"
          class="pay-btn"
        >
          {{ paying ? '创建订单中...' : '立即支付' }}
        </el-button>
      </div>
    </div>

    <!-- ==================== 支付弹窗 ==================== -->
    <el-dialog
      v-model="payDialogVisible"
      :title="payStatus === 'success' ? '支付成功' : payStatus === 'failed' ? '支付失败' : '正在支付'"
      width="500px"
      :close-on-click-modal="false"
      :show-close="payStatus === 'success' || payStatus === 'failed'"
      @close="stopPolling"
    >
      <!-- 支付宝 HTML 表单 -->
      <div v-if="payHtml" class="alipay-form-wrapper">
        <div v-html="payHtml"></div>
      </div>

      <!-- 微信二维码 -->
      <div v-else-if="qrCodeUrl" class="wechat-qr-wrapper">
        <p class="qr-tip">请使用微信扫描下方二维码完成支付</p>
        <el-image
          :src="qrCodeUrl"
          fit="contain"
          class="qr-image"
          alt="微信支付二维码"
        >
          <template #error>
            <div class="qr-error">
              <el-icon :size="48"><WarningFilled /></el-icon>
              <p>二维码加载失败</p>
            </div>
          </template>
        </el-image>
        <p class="qr-hint">订单号：{{ orderNo }}</p>
      </div>

      <!-- 支付中 -->
      <div v-if="payStatus === 'pending'" class="paying-status">
        <el-icon class="loading-icon" :size="32"><Loading /></el-icon>
        <p>等待支付完成...</p>
        <p class="paying-hint">请在新打开的页面中完成支付</p>
      </div>

      <!-- 支付成功 -->
      <div v-if="payStatus === 'success'" class="pay-success">
        <el-icon :size="60" color="#67c23a"><CircleCheckFilled /></el-icon>
        <p class="success-text">支付成功！</p>
        <p>点数已到账，即将跳转...</p>
      </div>

      <!-- 支付失败 -->
      <div v-if="payStatus === 'failed'" class="pay-failed">
        <el-icon :size="60" color="#f56c6c"><CircleCloseFilled /></el-icon>
        <p class="failed-text">支付失败</p>
        <p>请重试或选择其他支付方式</p>
      </div>

      <template #footer v-if="payStatus === 'failed' || payStatus === 'pending'">
        <el-button @click="retryPay" type="primary">重新支付</el-button>
      </template>
      <template #footer v-else-if="payStatus === 'success'">
        <el-button @click="payDialogVisible = false" type="primary">完成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.recharge-online-page {
  max-width: 960px;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  margin: 0 0 6px;
}

.page-desc {
  font-size: 14px;
  color: #909399;
  margin: 0;
}

/* 步骤条 */
.recharge-steps {
  margin-bottom: 32px;
}

.recharge-steps :deep(.el-step__title) {
  font-weight: 600;
}

/* 步骤内容 */
.step-content {
  min-height: 300px;
}

.step-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #ebeef5;
}

/* ==================== 套餐卡片网格 ==================== */
.packages-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

.package-card {
  position: relative;
  background: #fff;
  border: 2px solid #e4e7ed;
  border-radius: 12px;
  padding: 20px 16px 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
  overflow: hidden;
}

.package-card:hover {
  border-color: #a0cfff;
  box-shadow: 0 4px 16px rgba(64, 158, 255, 0.1);
  transform: translateY(-2px);
}

.package-card.active {
  border-color: #409eff;
  box-shadow: 0 4px 20px rgba(64, 158, 255, 0.2);
  background: #ecf5ff;
}

.hot-badge {
  position: absolute;
  top: 8px;
  right: 8px;
}

.package-name {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

.package-amount {
  margin-bottom: 12px;
}

.currency {
  font-size: 18px;
  font-weight: 600;
  color: #f56c6c;
}

.amount-num {
  font-size: 32px;
  font-weight: 800;
  color: #f56c6c;
  line-height: 1;
}

.package-info {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}

.info-label {
  font-size: 13px;
  color: #909399;
}

.points-value {
  font-size: 15px;
  font-weight: 600;
  color: #409eff;
}

.package-bonus {
  min-height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.no-bonus-text {
  font-size: 12px;
  color: #c0c4cc;
}

/* ==================== 支付方式卡片 ==================== */
.channels-grid {
  display: flex;
  gap: 24px;
  margin-bottom: 28px;
  justify-content: center;
}

.channel-card {
  width: 220px;
  background: #fff;
  border: 2px solid #e4e7ed;
  border-radius: 12px;
  padding: 32px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
}

.channel-card:hover {
  border-color: #a0cfff;
  box-shadow: 0 4px 16px rgba(64, 158, 255, 0.1);
  transform: translateY(-2px);
}

.channel-card.active {
  border-color: #409eff;
  box-shadow: 0 4px 20px rgba(64, 158, 255, 0.2);
  background: #ecf5ff;
}

.channel-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.channel-name {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 8px;
}

.channel-desc {
  font-size: 13px;
  color: #909399;
}

/* ==================== 订单摘要 ==================== */
.order-summary-card {
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  margin-bottom: 28px;
  max-width: 520px;
}

.order-summary-card :deep(.el-card__body) {
  padding: 24px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f2f3f5;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-label {
  font-size: 14px;
  color: #606266;
}

.summary-value {
  font-size: 14px;
  color: #303133;
  font-weight: 500;
}

.amount-highlight {
  font-size: 22px;
  font-weight: 700;
  color: #f56c6c;
}

.points-highlight {
  font-size: 16px;
  font-weight: 600;
  color: #409eff;
}

.bonus-highlight {
  font-size: 14px;
  font-weight: 600;
  color: #e6a23c;
}

/* ==================== 步骤操作按钮 ==================== */
.step-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 8px;
}

.pay-btn {
  min-width: 160px;
  height: 44px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
}

/* ==================== 支付弹窗 ==================== */
.alipay-form-wrapper {
  display: flex;
  justify-content: center;
}

.wechat-qr-wrapper {
  text-align: center;
}

.qr-tip {
  font-size: 14px;
  color: #606266;
  margin-bottom: 16px;
}

.qr-image {
  width: 220px;
  height: 220px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  margin: 0 auto;
  display: block;
}

.qr-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 12px;
}

.qr-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #909399;
  padding: 40px;
}

.paying-status,
.pay-success,
.pay-failed {
  text-align: center;
  padding: 24px 0;
}

.loading-icon {
  animation: spin 1s linear infinite;
  color: #409eff;
  margin-bottom: 12px;
}

.success-text {
  font-size: 18px;
  font-weight: 600;
  color: #67c23a;
  margin: 12px 0 8px;
}

.failed-text {
  font-size: 18px;
  font-weight: 600;
  color: #f56c6c;
  margin: 12px 0 8px;
}

.paying-hint {
  font-size: 13px;
  color: #909399;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ==================== 响应式 ==================== */
@media (max-width: 900px) {
  .packages-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 640px) {
  .packages-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .channels-grid {
    flex-direction: column;
    align-items: center;
  }

  .channel-card {
    width: 100%;
    max-width: 280px;
  }

  .step-actions {
    flex-direction: column;
    align-items: center;
  }

  .step-actions .el-button {
    width: 100%;
    max-width: 320px;
  }

  .page-header h2 {
    font-size: 18px;
  }

  .amount-num {
    font-size: 26px;
  }
}

@media (max-width: 400px) {
  .packages-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}
</style>
