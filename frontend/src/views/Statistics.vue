<template>
  <div>
    <h2>收支统计分析</h2>
    <div style="margin-bottom: 20px; display: flex; align-items: center;">
      <span style="margin-right: 10px;">选择月份：</span>
      <el-date-picker
        v-model="currentMonth"
        type="month"
        placeholder="选择月份"
        format="YYYY-MM"
        value-format="YYYY-MM"
        @change="loadAnalysis"
      />
    </div>
    <el-row :gutter="20">
      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header>支出分类占比</template>
          <div ref="expensePieChart" style="height: 300px"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header>收入分类占比</template>
          <div ref="incomePieChart" style="height: 300px"></div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="24">
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
import { apiGetAnalysis, apiGetCategories } from '@/api'

const expensePieChart = ref(null)
const incomePieChart = ref(null)
const lineChart = ref(null)
const currentMonth = ref(new Date().toISOString().slice(0, 7))

const loadAnalysis = async () => {
  const month = currentMonth.value
  try {
    const [analysisRes, categoriesRes] = await Promise.all([
      apiGetAnalysis(month),
      apiGetCategories().catch(() => ({ data: { code: 200, data: [] } }))
    ])

    if (analysisRes.data.code === 200) {
      const { category_expense_pie, daily_trend_line, record_list } = analysisRes.data.data

      // 渲染支出饼图
      renderPieChart(expensePieChart, category_expense_pie || [], '支出')

      // 计算收入分类占比
      const categories = categoriesRes.data.code === 200 ? categoriesRes.data.data : []
      const categoryMap = {}
      categories.forEach(cat => {
        categoryMap[cat.category_id] = cat.category_name
      })

      const incomeMap = {}
      if (record_list && record_list.length > 0) {
        record_list.filter(r => r.type === 'income').forEach(r => {
          const name = r.category_name || categoryMap[r.category_id] || '未分类'
          incomeMap[name] = (incomeMap[name] || 0) + parseFloat(r.amount)
        })
      }
      const incomePieData = Object.entries(incomeMap).map(([name, value]) => ({
        category_name: name,
        total_amount: value
      }))
      renderPieChart(incomePieChart, incomePieData, '收入')

      // 渲染趋势图
      renderLineChart(daily_trend_line || [])
    }
  } catch (error) {
    console.error('获取统计数据失败', error)
  }
}

const renderPieChart = (chartRef, data, type) => {
  if (!chartRef.value) return
  const chart = echarts.init(chartRef.value)
  chart.setOption({
    tooltip: { trigger: 'item' },
    legend: { orient: 'vertical', left: 'left' },
    series: [
      {
        name: type + '分类',
        type: 'pie',
        radius: '50%',
        data: data.map(item => ({
          name: item.category_name,
          value: item.total_amount
        })),
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowOffsetX: 0,
            shadowColor: 'rgba(0, 0, 0, 0.5)'
          }
        }
      }
    ]
  })
}

// 折线图渲染函数
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

onMounted(() => {
  loadAnalysis()
})
</script>