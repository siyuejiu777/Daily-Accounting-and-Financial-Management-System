<template>
  <div>
    <h2>仪表盘</h2>
    <el-row :gutter="20">
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>今日收入</template>
          <div class="amount income">¥ {{ todayIncome }}</div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>今日支出</template>
          <div class="amount expense">¥ {{ todayExpense }}</div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>月度预算剩余</template>
          <div :class="['amount', isOverBudget ? 'over' : 'normal']">¥ {{ remainingBudget }}</div>
        </el-card>
      </el-col>
    </el-row>
    <el-card style="margin-top: 20px;">
      <template #header>快速记账</template>
      <el-input v-model="searchKeyword" placeholder="搜索记账记录..." />
      <el-button type="primary" style="margin-top: 10px;" @click="router.push('/record')">添加新记录</el-button>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiGetAnalysis } from '@/api'

const router = useRouter()
const todayIncome = ref(0)
const todayExpense = ref(0)
const remainingBudget = ref(0)
const isOverBudget = ref(false)
const searchKeyword = ref('')

onMounted(async () => {
  try {
    // 获取当月数据
    const month = new Date().toISOString().slice(0, 7) // 格式：YYYY-MM
    const res = await apiGetAnalysis(month)
    if (res.data.code === 200) {
      const { daily_trend_line, category_expense_pie } = res.data.data

      // 提取今日收支
      const todayStr = new Date().toISOString().slice(0, 10) // YYYY-MM-DD
      const todayData = daily_trend_line.find(item => item.date === todayStr)
      if (todayData) {
        todayIncome.value = todayData.income || 0
        todayExpense.value = todayData.expense || 0
      }

      // 如果后端 analysis.php 还返回了预算相关字段（如 remaining_budget, is_over_budget），直接取用
      // 假设返回的数据里包含：
      if (res.data.data.remaining_budget !== undefined) {
        remainingBudget.value = res.data.data.remaining_budget
        isOverBudget.value = res.data.data.is_over_budget || false
      } else {
        // 如果不包含，就调用 budget_set.php（GET 或 POST）来获取
        // 这里先留空，或直接设为0
        remainingBudget.value = 0
      }
    }
  } catch (error) {
    console.error('获取仪表盘数据失败:', error)
  }
})
</script>

<style scoped>
.amount {
  font-size: 28px;
  font-weight: bold;
}
.income { color: #67C23A; }
.expense { color: #F56C6C; }
.normal { color: #409EFF; }
.over { color: #FF0000; }
</style>