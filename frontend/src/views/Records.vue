<template>
  <div>
    <h2>记账列表</h2>
    <div style="margin-bottom: 20px; display: flex; align-items: center;">
      <span style="margin-right: 10px;">选择月份：</span>
      <el-date-picker
        v-model="currentMonth"
        type="month"
        placeholder="选择月份"
        format="YYYY-MM"
        value-format="YYYY-MM"
        @change="fetchRecords"
      />
    </div>
    
    <!-- 搜索关键词提示 -->
    <el-alert
      v-if="searchKeyword"
      title="搜索结果"
      :description="`正在按 “${searchKeyword}” 筛选，共找到 ${filteredRecords.length} 条记录`"
      type="info"
      closable
      @close="searchKeyword = ''"
      style="margin-bottom: 20px;"
    />
    
    <!-- 分类筛选提示 -->
    <el-alert
      v-if="filterCategoryId && !searchKeyword"
      title="分类筛选"
      :description="`正在查看分类为 “${currentCategoryName}” 的记录，共 ${filteredRecords.length} 条`"
      type="info"
      closable
      @close="clearCategoryFilter"
      style="margin-bottom: 20px;"
    />

    <el-table :data="filteredRecords" border v-loading="loading">
      <el-table-column prop="amount" label="金额" />
      <el-table-column prop="type" label="类型">
        <template #default="{ row }">
          <el-tag :type="row.type === 'expense' ? 'danger' : 'success'">
            {{ row.type === 'expense' ? '支出' : '收入' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="category_name" label="分类" />
      <el-table-column prop="record_time" label="时间" />
      <el-table-column prop="note" label="备注" />
      <el-table-column label="操作">
        <template #default="{ row }">
          <el-button size="small" @click="openEditDialog(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="deleteRecord(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-empty v-if="!loading && filteredRecords.length === 0" description="暂无记录" />

    <!-- 编辑记录弹窗 -->
    <el-dialog v-model="editDialogVisible" title="编辑记录" width="500px">
      <el-form :model="editForm" label-width="80px">
        <el-form-item label="金额">
          <el-input-number v-model="editForm.amount" :min="0.01" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="类型">
          <el-radio-group v-model="editForm.type">
            <el-radio value="income">收入</el-radio>
            <el-radio value="expense">支出</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="editForm.category_id" placeholder="请选择分类" style="width: 100%">
            <el-option
              v-for="cat in filteredEditCategories"
              :key="cat.category_id"
              :label="cat.category_name"
              :value="cat.category_id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="时间">
          <el-date-picker
            v-model="editForm.record_time"
            type="datetime"
            placeholder="选择日期时间"
            style="width: 100%"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="editForm.note" type="textarea" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitEdit" :loading="editSubmitting">保存修改</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
// import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { apiGetAnalysis, apiDeleteRecord, apiUpdateRecord, apiAddRecord, apiGetCategories } from '@/api'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'

const records = ref([])
const loading = ref(false)
const currentMonth = ref(new Date().toISOString().slice(0, 7))
const route = useRoute()
const searchKeyword = ref('')
const filterCategoryId = ref(null)

const editDialogVisible = ref(false)
const editSubmitting = ref(false)
const categories = ref([])
const editForm = reactive({
  category_id: null,
  old_category_id: null,      // 新增：原始分类ID，用于定位旧记录
  old_record_time: '',        // 新增：原始时间，用于定位旧记录
  amount: null,
  type: 'expense',
  record_time: '',
  note: ''
})

// 计算当前筛选的分类名称（用于提示）
const currentCategoryName = computed(() => {
  if (!filterCategoryId.value) return ''
  const cat = categories.value.find(c => c.category_id == filterCategoryId.value)
  return cat ? cat.category_name : ''
})

// 过滤后的记录列表：先按分类，再按搜索关键词
const filteredRecords = computed(() => {
  let result = records.value
  if (filterCategoryId.value) {
    result = result.filter(record => record.category_id == filterCategoryId.value)
  }
  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(record =>
      (record.note && record.note.toLowerCase().includes(keyword)) ||
      (record.category_name && record.category_name.toLowerCase().includes(keyword)) ||
      (record.amount && record.amount.toString().includes(keyword)) ||
      (record.type && record.type.includes(keyword))
    )
  }
  return result
})

const filteredEditCategories = computed(() => {
  return categories.value.filter(cat => cat.type === editForm.type)
})

const fetchRecords = async () => {
  loading.value = true
  try {
    const res = await apiGetAnalysis(currentMonth.value)
    if (res.data.code === 200) {
      records.value = res.data.data.record_list || []
    }
  } catch (error) {
    ElMessage.error('获取记录失败')
  } finally {
    loading.value = false
  }
}

watch(() => route.query.keyword, (newKeyword) => {
  searchKeyword.value = newKeyword || ''
}, { immediate: true })

watch(() => route.query.category_id, (newId) => {
  filterCategoryId.value = newId ? parseInt(newId) : null
}, { immediate: true })

const clearCategoryFilter = () => {
  filterCategoryId.value = null
  // 同时清除路由参数（可选）
  if (route.query.category_id) {
    router.replace({ path: '/records', query: { ...route.query, category_id: undefined } })
  }
}

// 在 loadCategories 函数中
const loadCategories = async () => {
  try {
    const res = await apiGetCategories()
    if (res.data.code === 200) {
      categories.value = res.data.data.map(cat => ({
        ...cat,
        category_id: Number(cat.category_id)  // 强制转为数字
      }))
    }
  } catch (e) {
    categories.value = []
  }
}

const openEditDialog = async (row) => {
  // 1. 确保分类数据已加载（如果已加载则不会重复请求）
  if (categories.value.length === 0) {
    await loadCategories()
  }
  
  // 2. 用 Vue 的 nextTick 确保 DOM 更新后再打开弹窗
  nextTick(() => {
    // 填充表单，数值全部转为数字
    editForm.category_id = Number(row.category_id)
    editForm.old_category_id = Number(row.category_id)
    editForm.old_record_time = row.record_time
    editForm.amount = parseFloat(row.amount)
    editForm.type = row.type
    editForm.record_time = row.record_time
    editForm.note = row.note || ''
    editDialogVisible.value = true
  })
}

const submitEdit = async () => {
  if (!editForm.amount || !editForm.category_id) {
    ElMessage.warning('金额和分类不能为空')
    return
  }
  editSubmitting.value = true
  try {
    // 判断分类或时间是否改变
    const categoryChanged = editForm.category_id !== editForm.old_category_id
    const timeChanged = editForm.record_time !== editForm.old_record_time

    if (categoryChanged || timeChanged) {
      // 联合主键发生变化，采用“先删旧，再添新”策略
      // 1. 删除原记录
      const delRes = await apiDeleteRecord({
        category_id: editForm.old_category_id,
        record_time: editForm.old_record_time
      })
      if (delRes.data.code !== 200) {
        ElMessage.error('删除原记录失败：' + (delRes.data.msg || ''))
        return
      }
      // 2. 添加新记录
      const addRes = await apiAddRecord({
        amount: editForm.amount,
        type: editForm.type,
        category_id: editForm.category_id,
        note: editForm.note,
        record_time: editForm.record_time || undefined
      })
      if (addRes.data.code === 200) {
        ElMessage.success('修改成功')
        editDialogVisible.value = false
        fetchRecords()
      } else {
        ElMessage.error('添加新记录失败：' + (addRes.data.msg || ''))
        // 这里可以考虑恢复被删除的记录，但演示环境下暂不处理
      }
    } else {
      // 未改变联合主键，直接更新其他字段
      const res = await apiUpdateRecord({
        category_id: editForm.category_id,
        old_record_time: editForm.old_record_time,
        amount: editForm.amount,
        type: editForm.type,
        note: editForm.note,
        record_time: editForm.record_time || undefined
      })
      if (res.data.code === 200) {
        ElMessage.success('修改成功')
        editDialogVisible.value = false
        fetchRecords()
      } else {
        ElMessage.error(res.data.msg || '修改失败')
      }
    }
  } catch (error) {
    ElMessage.error('请求失败，请稍后重试')
  } finally {
    editSubmitting.value = false
  }
}

const deleteRecord = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    const res = await apiDeleteRecord({
      category_id: row.category_id,
      record_time: row.record_time
    })
    if (res.data.code === 200) {
      ElMessage.success('删除成功')
      fetchRecords()
    } else {
      ElMessage.error(res.data.msg || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败，请稍后重试')
    }
  }
}

onMounted(() => {
  loadCategories()
  fetchRecords()
})
</script>