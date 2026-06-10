<template>
  <div>
    <h2>添加记录</h2>
    <el-form :model="form" label-width="80px" style="max-width: 500px">
      <el-form-item label="金额">
        <el-input-number v-model="form.amount" :min="0.01" :precision="2" style="width: 100%" />
      </el-form-item>
      <el-form-item label="类型">
        <el-radio-group v-model="form.type">
          <el-radio value="income">收入</el-radio>
          <el-radio value="expense">支出</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="分类">
        <el-select v-model="form.category_id" placeholder="请选择分类" style="width: 100%">
          <el-option
            v-for="cat in filteredCategories"
            :key="cat.category_id"
            :label="cat.category_name"
            :value="cat.category_id"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="时间">
        <el-date-picker
          v-model="form.record_time"
          type="datetime"
          placeholder="选择日期时间"
          style="width: 100%"
          value-format="YYYY-MM-DD HH:mm:ss"
        />
      </el-form-item>
      <el-form-item label="备注">
        <el-input v-model="form.note" type="textarea" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="submitRecord" :loading="submitting">保存</el-button>
        <el-button @click="router.back()">取消</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiAddRecord, apiGetCategories } from '@/api'
import { ElMessage } from 'element-plus'

const router = useRouter()
const submitting = ref(false)
const categories = ref([])

const form = reactive({
  amount: null,
  type: 'expense',      // 默认支出
  category_id: null,
  record_time: '',       // 不填则后端自动填当前时间
  note: '',
  user_id: null          // 暂时需要传
})

// 根据类型筛选分类（收入时只显示收入分类，支出时只显示支出分类）
const filteredCategories = computed(() => {
  return categories.value.filter(cat => cat.type === form.type)
})

onMounted(async () => {
  // 获取 user_id（暂时从 localStorage 取，后续 A 改 Session 后删除这行）
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  form.user_id = user.user_id || 1  // 如果 localStorage 没有，默认 1（测试用）

  // 获取分类列表（如果接口还没好，先用 Mock）
  try {
    const res = await apiGetCategories()
    if (res.data.code === 200) {
      categories.value = res.data.data
    }
  } catch (e) {
    // Mock 数据兜底
    categories.value = [
      { category_id: 1, category_name: '餐饮美食', type: 'expense' },
      { category_id: 2, category_name: '交通出行', type: 'expense' },
      { category_id: 7, category_name: '薪资收入', type: 'income' }
    ]
  }
})

const submitRecord = async () => {
  if (!form.amount || !form.category_id) {
    ElMessage.warning('金额和分类不能为空')
    return
  }
  submitting.value = true
  try {
    const res = await apiAddRecord({
      user_id: form.user_id,
      amount: form.amount,
      type: form.type,
      category_id: form.category_id,
      note: form.note,
      record_time: form.record_time || undefined  // 空则后端自动生成
    })
    if (res.data.code === 200) {
      ElMessage.success('记账成功！')
      router.push('/records')
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('提交失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}
</script>