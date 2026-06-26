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
          <template #header>
            月度预算剩余
            <el-button link @click="showBudgetDialog = true" style="float: right;">设置预算</el-button>
          </template>
          <div :class="['amount', isOverBudget ? 'over' : 'normal']">¥ {{ remainingBudget }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-card style="margin-top: 20px;">
      <template #header>快速记账</template>
      <el-input
        v-model="searchKeyword"
        placeholder="搜索记账记录..."
        @keyup.enter="handleSearch"
      />
      <el-button type="primary" style="margin-top: 10px;" @click="router.push('/record')">添加新记录</el-button>
    </el-card>

    <!-- 设置预算弹窗 -->
    <el-dialog v-model="showBudgetDialog" title="设置月度预算" width="400px">
      <el-form :model="budgetForm" label-width="80px">
        <el-form-item label="月份">
          <el-input v-model="budgetForm.month" placeholder="YYYY-MM" disabled />
        </el-form-item>
        <el-form-item label="预算额度">
          <el-input-number v-model="budgetForm.amount" :min="0" :precision="2" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showBudgetDialog = false">取消</el-button>
        <el-button type="primary" @click="submitBudget" :loading="budgetSubmitting">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiGetAnalysis, apiSetBudget, apiGetBudgetStatus } from '@/api'
import { ElMessage } from 'element-plus'

const router = useRouter()
const todayIncome = ref(0)
const todayExpense = ref(0)
const remainingBudget = ref(0)
const isOverBudget = ref(false)
const searchKeyword = ref('')

const showBudgetDialog = ref(false)
const budgetSubmitting = ref(false)
const budgetForm = reactive({
  month: new Date().toISOString().slice(0, 7),
  amount: 0
})

const getLocalDateString = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getLocalMonthString = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  return `${year}-${month}`
}

const loadBudget = async () => {
  try {
    const month = getLocalMonthString()
    const res = await apiGetBudgetStatus(month)
    if (res.data.code === 200) {
      const { remaining_budget, is_over_budget, budget_amount } = res.data.data
      remainingBudget.value = remaining_budget
      isOverBudget.value = is_over_budget
      // 将预算额度同步到弹窗表单中，方便修改
      budgetForm.amount = budget_amount
      budgetForm.month = month
    }
  } catch (error) {
    console.error('获取预算状态失败:', error)
  }
}

const loadDashboard = async () => {
  try {
    const month = getLocalMonthString()
    const res = await apiGetAnalysis(month)
    if (res.data.code === 200) {
      const { daily_trend_line } = res.data.data
      const todayStr = getLocalDateString()
      const todayData = daily_trend_line.find(item => item.date === todayStr)
      if (todayData) {
        todayIncome.value = todayData.income || 0
        todayExpense.value = todayData.expense || 0
      } else {
        todayIncome.value = 0
        todayExpense.value = 0
      }
    }
  } catch (error) {
    console.error('获取仪表盘数据失败:', error)
  }
}

const submitBudget = async () => {
  if (budgetForm.amount <= 0) {
    ElMessage.warning('预算额度必须大于0')
    return
  }
  budgetSubmitting.value = true
  try {
    const res = await apiSetBudget(budgetForm.month, budgetForm.amount)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      showBudgetDialog.value = false
      // 重新获取预算状态（更新剩余预算）
      await loadBudget()
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('设置预算失败，请稍后重试')
  } finally {
    budgetSubmitting.value = false
  }
}

const handleSearch = () => {
  const keyword = searchKeyword.value.trim()
  if (keyword) {
    router.push({ path: '/records', query: { keyword } })
  } else {
    router.push('/records')
  }
}

onMounted(async () => {
  await loadBudget()      // 先加载预算，获取余额和超支状态
  await loadDashboard()   // 再加载今日收支
})
</script>

<style scoped>
.amount { font-size: 28px; font-weight: bold; }
.income { color: #67C23A; }
.expense { color: #F56C6C; }
.normal { color: #409EFF; }
.over { color: #FF0000; }
</style>