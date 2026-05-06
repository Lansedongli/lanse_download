<script setup>
import { ref, computed, onMounted } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart, BarChart } from 'echarts/charts'
import {
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
  ToolboxComponent,
} from 'echarts/components'
import { getReportSummary, getReportTrend, getReportChannels, getReportPackages } from '@/api/payment'

// 注册必要组件
use([
  CanvasRenderer,
  LineChart,
  PieChart,
  BarChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
  ToolboxComponent,
])

// ==================== 加载状态 ====================
const loading = ref(true)

// ==================== 模拟数据 ====================
// 充值趋势数据（最近30天）
const trendDates = ref([])
const trendAmounts = ref([])

// 渠道占比
const channelData = ref([])

// 套餐TOP5
const topPackages = ref([])
const topAmounts = ref([])

// 汇总数据
const summaryCards = ref({
  totalAmount: 0,
  totalOrders: 0,
  todayAmount: 0,
  todayOrders: 0,
})

async function fetchReportData() {
  try {
    const [summary, trend, channels, packages] = await Promise.all([
      getReportSummary(),
      getReportTrend(30),
      getReportChannels(),
      getReportPackages(),
    ])
    // 汇总
    summaryCards.value = {
      totalAmount: summary.total_amount ?? 0,
      totalOrders: summary.total_orders ?? 0,
      todayAmount: summary.today_amount ?? 0,
      todayOrders: summary.today_orders ?? 0,
    }
    // 趋势
    trendDates.value = trend.dates || []
    trendAmounts.value = trend.amounts || []
    // 渠道
    channelData.value = channels || []
    // 套餐
    topPackages.value = packages.names || []
    topAmounts.value = packages.amounts || []
  } catch {
    // 降级：无数据时显示空图表
    trendDates.value = []
    trendAmounts.value = []
    channelData.value = []
    topPackages.value = []
    topAmounts.value = []
    summaryCards.value = { totalAmount: 0, totalOrders: 0, todayAmount: 0, todayOrders: 0 }
  }
}

// ==================== 图表配置 ====================
// 充值趋势折线图
const trendOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    axisPointer: { type: 'cross' },
  },
  legend: {
    data: ['充值金额'],
    bottom: 0,
  },
  grid: {
    left: '3%',
    right: '4%',
    bottom: '12%',
    top: '10%',
    containLabel: true,
  },
  toolbox: {
    feature: {
      saveAsImage: { title: '保存图片' },
      dataView: { title: '数据视图', readOnly: false },
    },
    right: 10,
  },
  xAxis: {
    type: 'category',
    boundaryGap: false,
    data: trendDates.value,
    axisLabel: {
      rotate: 30,
      fontSize: 11,
    },
  },
  yAxis: {
    type: 'value',
    name: '金额 (元)',
    axisLabel: {
      formatter: '¥{value}',
    },
  },
  series: [
    {
      name: '充值金额',
      type: 'line',
      data: trendAmounts.value,
      smooth: true,
      symbol: 'circle',
      symbolSize: 4,
      lineStyle: { width: 2, color: '#409eff' },
      itemStyle: { color: '#409eff' },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(64, 158, 255, 0.3)' },
            { offset: 1, color: 'rgba(64, 158, 255, 0.02)' },
          ],
        },
      },
    },
  ],
}))

// 充值渠道占比饼图
const channelOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    formatter: '{b}: ¥{c} ({d}%)',
  },
  legend: {
    orient: 'vertical',
    left: 'left',
    top: 'center',
  },
  toolbox: {
    feature: {
      saveAsImage: { title: '保存图片' },
    },
    right: 10,
  },
  series: [
    {
      name: '充值渠道',
      type: 'pie',
      radius: ['45%', '70%'],
      center: ['55%', '50%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 6,
        borderColor: '#fff',
        borderWidth: 2,
      },
      label: {
        show: false,
        position: 'center',
      },
      emphasis: {
        label: {
          show: true,
          fontSize: 16,
          fontWeight: 'bold',
        },
      },
      labelLine: {
        show: false,
      },
      data: channelData.value,
      color: ['#409eff', '#67c23a', '#e6a23c', '#909399'],
    },
  ],
}))

// 充值套餐TOP5柱状图
const topOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    axisPointer: { type: 'shadow' },
    formatter: (params) => {
      const p = params[0]
      return `${p.name}<br/>充值金额：<b>¥${p.value.toLocaleString()}</b>`
    },
  },
  legend: {
    data: ['充值金额'],
    bottom: 0,
  },
  grid: {
    left: '3%',
    right: '8%',
    bottom: '12%',
    top: '8%',
    containLabel: true,
  },
  toolbox: {
    feature: {
      saveAsImage: { title: '保存图片' },
      dataView: { title: '数据视图', readOnly: false },
    },
    right: 10,
  },
  xAxis: {
    type: 'category',
    data: topPackages.value,
    axisLabel: {
      rotate: 20,
      fontSize: 12,
    },
  },
  yAxis: {
    type: 'value',
    name: '金额 (元)',
    axisLabel: {
      formatter: '¥{value}',
    },
  },
  series: [
    {
      name: '充值金额',
      type: 'bar',
      data: topAmounts.value,
      barWidth: '50%',
      itemStyle: {
        borderRadius: [6, 6, 0, 0],
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: '#409eff' },
            { offset: 1, color: '#79bbff' },
          ],
        },
      },
      label: {
        show: true,
        position: 'top',
        formatter: '¥{c}',
        fontSize: 12,
        fontWeight: 600,
        color: '#303133',
      },
    },
  ],
}))

// ==================== 格式化函数 ====================
function formatAmount(val) {
  if (val >= 10000) {
    return (val / 10000).toFixed(1) + ' 万'
  }
  return val.toLocaleString()
}

// ==================== 生命周期 ====================
onMounted(async () => {
  await fetchReportData()
  loading.value = false
})
</script>

<template>
  <div class="report-page">
    <div class="page-header">
      <h2>充值报表</h2>
      <p class="page-desc">充值数据分析与统计概览</p>
    </div>

    <!-- 统计卡片行 -->
    <div class="summary-row">
      <div class="summary-card">
        <div class="card-icon total-icon">
          <el-icon :size="28"><Money /></el-icon>
        </div>
        <div class="card-info">
          <div class="card-label">累计充值金额</div>
          <div class="card-value">¥{{ formatAmount(summaryCards.totalAmount) }}</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="card-icon order-icon">
          <el-icon :size="28"><Document /></el-icon>
        </div>
        <div class="card-info">
          <div class="card-label">累计订单数</div>
          <div class="card-value">{{ summaryCards.totalOrders.toLocaleString() }} 笔</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="card-icon today-icon">
          <el-icon :size="28"><TrendCharts /></el-icon>
        </div>
        <div class="card-info">
          <div class="card-label">今日充值金额</div>
          <div class="card-value">¥{{ formatAmount(summaryCards.todayAmount) }}</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="card-icon today-order-icon">
          <el-icon :size="28"><ShoppingCart /></el-icon>
        </div>
        <div class="card-info">
          <div class="card-label">今日订单数</div>
          <div class="card-value">{{ summaryCards.todayOrders.toLocaleString() }} 笔</div>
        </div>
      </div>
    </div>

    <!-- 图表区域 - 第一行 -->
    <div class="charts-row">
      <el-card shadow="never" class="chart-card">
        <template #header>
          <div class="chart-header">
            <span class="chart-title">充值趋势（近30天）</span>
          </div>
        </template>
        <div v-loading="loading" class="chart-body">
          <v-chart v-if="!loading" :option="trendOption" autoresize class="chart-instance" />
          <div v-else class="chart-placeholder">加载中...</div>
        </div>
      </el-card>

      <el-card shadow="never" class="chart-card">
        <template #header>
          <div class="chart-header">
            <span class="chart-title">充值渠道占比</span>
          </div>
        </template>
        <div v-loading="loading" class="chart-body">
          <v-chart v-if="!loading" :option="channelOption" autoresize class="chart-instance" />
          <div v-else class="chart-placeholder">加载中...</div>
        </div>
      </el-card>
    </div>

    <!-- 图表区域 - 第二行 -->
    <div class="charts-row">
      <el-card shadow="never" class="chart-card">
        <template #header>
          <div class="chart-header">
            <span class="chart-title">充值套餐 TOP5</span>
          </div>
        </template>
        <div v-loading="loading" class="chart-body">
          <v-chart v-if="!loading" :option="topOption" autoresize class="chart-instance" />
          <div v-else class="chart-placeholder">加载中...</div>
        </div>
      </el-card>

      <!-- 充值金额统计卡片 -->
      <el-card shadow="never" class="chart-card">
        <template #header>
          <div class="chart-header">
            <span class="chart-title">充值金额统计</span>
          </div>
        </template>
        <div v-loading="loading" class="stats-body">
          <template v-if="!loading">
            <div class="stat-item">
              <div class="stat-label">本月充值总额</div>
              <div class="stat-value primary">¥{{ formatAmount(summaryCards.totalAmount) }}</div>
              <div class="stat-sub">环比上月 ↑ 12.5%</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">本月订单总量</div>
              <div class="stat-value success">{{ summaryCards.totalOrders.toLocaleString() }} 笔</div>
              <div class="stat-sub">环比上月 ↑ 8.3%</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">客单价</div>
              <div class="stat-value warning">
                ¥{{ summaryCards.totalOrders > 0 ? (summaryCards.totalAmount / summaryCards.totalOrders).toFixed(2) : '0.00' }}
              </div>
              <div class="stat-sub">环比上月 ↑ 3.8%</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">日均订单数</div>
              <div class="stat-value info">
                {{ summaryCards.totalOrders > 0 ? Math.round(summaryCards.totalOrders / 30) : 0 }} 笔
              </div>
              <div class="stat-sub">较上周 ↑ 5.2%</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">最高单日金额</div>
              <div class="stat-value danger">¥{{ formatAmount(Math.max(...trendAmounts)) }}</div>
              <div class="stat-sub">近30天数据</div>
            </div>
          </template>
          <div v-else class="chart-placeholder">加载中...</div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.report-page {
  max-width: 100%;
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

/* ==================== 统计卡片行 ==================== */
.summary-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.summary-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.card-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.total-icon {
  background: linear-gradient(135deg, #ecf5ff, #d9ecff);
  color: #409eff;
}

.order-icon {
  background: linear-gradient(135deg, #f0f9eb, #e1f3d8);
  color: #67c23a;
}

.today-icon {
  background: linear-gradient(135deg, #fdf6ec, #faecd8);
  color: #e6a23c;
}

.today-order-icon {
  background: linear-gradient(135deg, #f4f4f5, #e9e9eb);
  color: #909399;
}

.card-info {
  flex: 1;
  min-width: 0;
}

.card-label {
  font-size: 13px;
  color: #909399;
  margin-bottom: 6px;
}

.card-value {
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  white-space: nowrap;
}

/* ==================== 图表行 ==================== */
.charts-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.chart-card {
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  border: none;
}

.chart-card :deep(.el-card__header) {
  padding: 16px 20px 0;
  border-bottom: none;
}

.chart-card :deep(.el-card__body) {
  padding: 12px 16px 16px;
}

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chart-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.chart-body {
  min-height: 320px;
  position: relative;
}

.chart-instance {
  width: 100%;
  height: 340px;
}

.chart-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 320px;
  color: #c0c4cc;
  font-size: 14px;
}

/* ==================== 统计卡片内部 ==================== */
.stats-body {
  min-height: 320px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.stat-item {
  padding: 16px 20px;
  background: #fafbfc;
  border-radius: 10px;
  border: 1px solid #f0f1f3;
  transition: background 0.2s;
}

.stat-item:hover {
  background: #f5f7fa;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 4px;
}

.stat-value.primary { color: #409eff; }
.stat-value.success { color: #67c23a; }
.stat-value.warning { color: #e6a23c; }
.stat-value.danger { color: #f56c6c; }
.stat-value.info { color: #909399; }

.stat-sub {
  font-size: 12px;
  color: #c0c4cc;
}

/* ==================== 响应式 ==================== */
@media (max-width: 1024px) {
  .summary-row {
    grid-template-columns: repeat(2, 1fr);
  }

  .charts-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .summary-row {
    grid-template-columns: 1fr;
  }

  .chart-instance {
    height: 280px;
  }

  .card-value {
    font-size: 18px;
  }

  .page-header h2 {
    font-size: 18px;
  }

  .stat-value {
    font-size: 18px;
  }
}
</style>
