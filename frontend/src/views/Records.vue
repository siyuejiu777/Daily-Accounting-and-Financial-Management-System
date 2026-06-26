<template>
  <div>
    <h2>记账列表</h2>
    
    <!-- 月份选择 -->
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

    <!-- 批量操作按钮栏 -->
    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
      <el-button 
        type="danger" 
        :disabled="selectedRecords.length === 0" 
        @click="handleBatchDelete"
      >
        批量删除 ({{ selectedRecords.length }})
      </el-button>
      <el-button 
        type="primary" 
        :disabled="selectedRecords.length === 0" 
        @click="showBatchUpdateDialog = true"
      >
        批量修改分类 ({{ selectedRecords.length }})
      </el-button>
      <span v-if="selectedRecords.length > 0" style="color: #666;">
        已选择 {{ selectedRecords.length }} 条记录
      </span>
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

    <!-- 记录表格 -->
    <el-table 
      :data="filteredRecords" 
      border 
      v-loading="loading"
      @selection-change="handleSelectionChange"
      ref="tableRef"
    >
      <el-table-column type="selection" width="55" />
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

    <!-- 编辑记录弹窗（原有） -->
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

    <!-- 批量修改分类弹窗（新增） -->
    <el-dialog v-model="showBatchUpdateDialog" title="批量修改分类" width="400px">
      <p style="margin-bottom: 15px;">将选中的 {{ selectedRecords.length }} 条记录修改为：</p>
      <el-select v-model="batchTargetCategoryId" placeholder="请选择目标分类" style="width: 100%">
        <el-option
          v-for="cat in categories"
          :key="cat.category_id"
          :label="cat.category_name + ' (' + (cat.type === 'expense' ? '支出' : '收入') + ')'"
          :value="cat.category_id"
        />
      </el-select>
      <template #footer>
        <el-button @click="showBatchUpdateDialog = false">取消</el-button>
        <el-button type="primary" @click="handleBatchUpdate" :loading="batchSubmitting">确认修改</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  apiGetAnalysis, apiDeleteRecord, apiUpdateRecord, apiAddRecord, apiGetCategories,
  apiBatchDeleteRecords, apiBatchUpdateCategory
} from '@/api'
import { ElMessage, ElMessageBox } from 'element-plus'

const router = useRouter()
const route = useRoute()

// ========== 列表数据与筛选 ==========
const records = ref([])
const loading = ref(false)
const currentMonth = ref(new Date().toISOString().slice(0, 7))
const searchKeyword = ref('')
const filterCategoryId = ref(null)
const categories = ref([])

// ========== 批量操作 ==========
const tableRef = ref(null)
const selectedRecords = ref([])
const showBatchUpdateDialog = ref(false)
const batchTargetCategoryId = ref(null)
const batchSubmitting = ref(false)

// ========== 编辑弹窗 ==========
const editDialogVisible = ref(false)
const editSubmitting = ref(false)
const editForm = reactive({
  category_id: null,
  old_category_id: null,
  old_record_time: '',
  amount: null,
  type: 'expense',
  record_time: '',
  note: ''
})

// ========== 计算属性 ==========
const currentCategoryName = computed(() => {
  if (!filterCategoryId.value) return ''
  const cat = categories.value.find(c => c.category_id == filterCategoryId.value)
  return cat ? cat.category_name : ''
})

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

// ========== 数据加载 ==========
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

const loadCategories = async () => {
  try {
    const res = await apiGetCategories()
    if (res.data.code === 200) {
      categories.value = res.data.data.map(cat => ({
        ...cat,
        category_id: Number(cat.category_id)
      }))
    }
  } catch (e) {
    categories.value = []
  }
}

// ========== 单条编辑 ==========
const openEditDialog = async (row) => {
  if (categories.value.length === 0) {
    await loadCategories()
  }
  nextTick(() => {
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
    const categoryChanged = editForm.category_id !== editForm.old_category_id
    const timeChanged = editForm.record_time !== editForm.old_record_time

    if (categoryChanged || timeChanged) {
      // 联合主键变化：先删旧记录，再添加新记录
      const delRes = await apiDeleteRecord({
        category_id: editForm.old_category_id,
        record_time: editForm.old_record_time
      })
      if (delRes.data.code !== 200) {
        ElMessage.error('删除原记录失败：' + (delRes.data.msg || ''))
        return
      }
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
      }
    } else {
      // 直接更新
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

// ========== 单条删除 ==========
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

// ========== 批量操作 ==========
const handleSelectionChange = (selection) => {
  selectedRecords.value = selection
}

const handleBatchDelete = async () => {
  if (selectedRecords.value.length === 0) {
    ElMessage.warning('请先选择记录')
    return
  }
  try {
    await ElMessageBox.confirm(
      `确定删除选中的 ${selectedRecords.value.length} 条记录吗？`,
      '批量删除',
      { type: 'warning' }
    )
    const recordsToDelete = selectedRecords.value.map(r => ({
      category_id: r.category_id,
      record_time: r.record_time
    }))
    const res = await apiBatchDeleteRecords(recordsToDelete)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      fetchRecords()
      tableRef.value?.clearSelection()
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('批量删除失败')
  }
}

const handleBatchUpdate = async () => {
  if (!batchTargetCategoryId.value) {
    ElMessage.warning('请选择目标分类')
    return
  }
  batchSubmitting.value = true
  try {
    const recordsToUpdate = selectedRecords.value.map(r => ({
      category_id: r.category_id,
      record_time: r.record_time
    }))
    const res = await apiBatchUpdateCategory(recordsToUpdate, batchTargetCategoryId.value)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      showBatchUpdateDialog.value = false
      batchTargetCategoryId.value = null
      fetchRecords()
      tableRef.value?.clearSelection()
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('批量修改失败')
  } finally {
    batchSubmitting.value = false
  }
}

const clearCategoryFilter = () => {
  filterCategoryId.value = null
  if (route.query.category_id) {
    router.replace({ path: '/records', query: { ...route.query, category_id: undefined } })
  }
}

// ========== 监听 ==========
watch(() => route.query.keyword, (newKeyword) => {
  searchKeyword.value = newKeyword || ''
}, { immediate: true })

watch(() => route.query.category_id, (newId) => {
  filterCategoryId.value = newId ? parseInt(newId) : null
}, { immediate: true })

// ========== 初始化 ==========
onMounted(() => {
  loadCategories()
  fetchRecords()
})
</script>