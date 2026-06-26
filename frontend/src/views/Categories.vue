<template>
  <div>
    <h2>收支分类管理</h2>
    <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
      <el-button type="primary" @click="openAddDialog">新增分类</el-button>
      <el-button type="danger" :disabled="selectedIds.length === 0" @click="handleBatchDelete">
        批量删除 ({{ selectedIds.length }})
      </el-button>
      <el-button type="warning" :disabled="selectedIds.length === 0" @click="showBatchTypeDialog = true">
        批量修改类型 ({{ selectedIds.length }})
      </el-button>
      <span v-if="selectedIds.length > 0" style="color: #666;">
        已选择 {{ selectedIds.length }} 个分类
      </span>
    </div>

    <el-table
      :data="categories"
      border
      v-loading="loading"
      @selection-change="handleSelectionChange"
      ref="tableRef"
    >
      <el-table-column type="selection" width="55" />
      <el-table-column prop="category_name" label="分类名称" />
      <el-table-column prop="type" label="类型">
        <template #default="{ row }">
          <el-tag :type="row.type === 'expense' ? 'danger' : 'success'">
            {{ row.type === 'expense' ? '支出' : '收入' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作">
        <template #default="{ row }">
          <el-button size="small" @click="viewRecords(row.category_id)">查看记录</el-button>
          <el-button size="small" type="danger" @click="deleteCategory(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-empty v-if="!loading && categories.length === 0" description="暂无分类数据" />

    <!-- 新增分类弹窗（原有） -->
    <el-dialog v-model="dialogVisible" title="新增分类" width="400px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="分类名称">
          <el-input v-model="form.category_name" placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="收支类型">
          <el-radio-group v-model="form.type">
            <el-radio value="expense">支出</el-radio>
            <el-radio value="income">收入</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAddCategory" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量修改类型弹窗（新增） -->
    <el-dialog v-model="showBatchTypeDialog" title="批量修改分类类型" width="400px">
      <p style="margin-bottom: 15px;">将选中的 {{ selectedIds.length }} 个分类的类型修改为：</p>
      <el-radio-group v-model="batchNewType">
        <el-radio value="expense">支出</el-radio>
        <el-radio value="income">收入</el-radio>
      </el-radio-group>
      <template #footer>
        <el-button @click="showBatchTypeDialog = false">取消</el-button>
        <el-button type="primary" @click="handleBatchUpdateType" :loading="batchSubmitting">确认修改</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  apiGetCategories, apiAddCategory, apiDeleteCategory,
  apiBatchDeleteCategories, apiBatchUpdateCategoryType
} from '@/api'
import { ElMessage, ElMessageBox } from 'element-plus'

const router = useRouter()
const categories = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const tableRef = ref(null)

// 批量操作相关
const selectedIds = ref([])          // 存储选中的 category_id
const showBatchTypeDialog = ref(false)
const batchNewType = ref('expense')
const batchSubmitting = ref(false)

const form = ref({
  category_name: '',
  type: 'expense'
})

// 表格复选框变化
const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(item => item.category_id)
}

// 跳转查看记录
const viewRecords = (categoryId) => {
  router.push({ path: '/records', query: { category_id: categoryId } })
}

// 打开新增弹窗
const openAddDialog = () => {
  form.value = { category_name: '', type: 'expense' }
  dialogVisible.value = true
}

// 获取分类列表
const fetchCategories = async () => {
  loading.value = true
  try {
    const res = await apiGetCategories()
    if (res.data.code === 200) {
      categories.value = res.data.data.map(cat => ({
        ...cat,
        category_id: Number(cat.category_id) // 确保数字类型
      }))
    } else {
      ElMessage.error(res.data.msg || '获取分类失败')
    }
  } catch (error) {
    ElMessage.error('获取分类失败，网络异常')
  } finally {
    loading.value = false
  }
}

// 新增分类
const submitAddCategory = async () => {
  if (!form.value.category_name) {
    ElMessage.warning('请输入分类名称')
    return
  }
  submitting.value = true
  try {
    const res = await apiAddCategory({
      category_name: form.value.category_name,
      type: form.value.type
    })
    if (res.data.code === 200) {
      ElMessage.success('新增分类成功')
      dialogVisible.value = false
      fetchCategories()
    } else {
      ElMessage.error(res.data.msg || '新增失败')
    }
  } catch (error) {
    ElMessage.error('请求失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

// 单条删除（原有）
const deleteCategory = async (row) => {
  try {
    await ElMessageBox.confirm(`确定删除分类“${row.category_name}”吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    const res = await apiDeleteCategory(row.category_id)
    if (res.data.code === 200) {
      ElMessage.success('删除成功')
      fetchCategories()
    } else {
      ElMessage.error(res.data.msg || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败，网络异常')
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择分类')
    return
  }
  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedIds.value.length} 个分类吗？注意：如果分类下已有账目则无法删除。`,
      '批量删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    const res = await apiBatchDeleteCategories(selectedIds.value)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      fetchCategories()
      // 清除选中状态
      if (tableRef.value) tableRef.value.clearSelection()
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('批量删除失败')
    }
  }
}

// 批量修改类型
const handleBatchUpdateType = async () => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择分类')
    return
  }
  batchSubmitting.value = true
  try {
    const res = await apiBatchUpdateCategoryType(selectedIds.value, batchNewType.value)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      showBatchTypeDialog.value = false
      fetchCategories()
      if (tableRef.value) tableRef.value.clearSelection()
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('批量修改失败')
  } finally {
    batchSubmitting.value = false
  }
}

onMounted(() => {
  fetchCategories()
})
</script>