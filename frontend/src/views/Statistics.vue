<template>
  <div>
    <h2>收支统计分析</h2>
    <el-row :gutter="20">
      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header>支出分类占比</template>
          <div ref="pieChart" style="height: 300px"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header>每日收支趋势</template>
          <div ref="lineChart" style="height: 300px"></div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import * as echarts from 'echarts'
import { apiGetAnalysis } from '@/api'

const pieChart = ref(null)
const lineChart = ref(null)

onMounted(async () => {
  const month = new Date().toISOString().slice(0, 7)
  try {
    const res = await apiGetAnalysis(month)
    if (res.data.code === 200) {
      const { category_expense_pie, daily_trend_line } = res.data.data
      renderPieChart(category_expense_pie || [])
      renderLineChart(daily_trend_line || [])
    }
  } catch (error) {
    console.error('获取统计数据失败', error)
  }
})

const renderPieChart = (data) => {
  if (!pieChart.value) return
  const chart = echarts.init(pieChart.value)
  chart.setOption({
    tooltip: { trigger: 'item' },
    legend: { orient: 'vertical', left: 'left' },
    series: [
      {
        type: 'pie',
        radius: '50%',
        data: data.map(item => ({ name: item.category_name, value: item.total_amount })),
        emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' } }
      }
    ]
  })
}

const renderLineChart = (data) => {
  if (!lineChart.value) return
  const chart = echarts.init(lineChart.value)
  chart.setOption({
    tooltip: { trigger: 'axis' },
    xAxis: { type: 'category', data: data.map(d => d.date) },
    yAxis: { type: 'value' },
    series: [
      { name: '收入', type: 'line', data: data.map(d => d.income), color: '#67C23A' },
      { name: '支出', type: 'line', data: data.map(d => d.expense), color: '#F56C6C' }
    ]
  })
}
</script>