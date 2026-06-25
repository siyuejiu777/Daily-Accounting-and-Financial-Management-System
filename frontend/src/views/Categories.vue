<template>
  <div>
    <h2>收支分类管理</h2>
    <el-button type="primary" style="margin-bottom: 20px;" @click="openAddDialog">
      新增分类
    </el-button>

    <!-- 分类列表表格 -->
    <el-table :data="categories" border v-loading="loading">
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

    <!-- 空状态 -->
    <el-empty v-if="!loading && categories.length === 0" description="暂无分类数据" />

    <!-- 新增分类弹窗 -->
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiGetCategories, apiAddCategory, apiDeleteCategory } from '@/api'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
const router = useRouter()
const categories = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)

const viewRecords = (categoryId) => {
  router.push({ path: '/records', query: { category_id: categoryId } })
}

const form = ref({
  category_name: '',
  type: 'expense'  // 默认支出
})

// 打开新增弹窗，并重置表单
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
      categories.value = res.data.data
    } else {
      ElMessage.error(res.data.msg || '获取分类失败')
    }
  } catch (error) {
    ElMessage.error('获取分类失败，网络异常')
  } finally {
    loading.value = false
  }
}

// 提交新增分类
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
      fetchCategories() // 刷新列表
    } else {
      ElMessage.error(res.data.msg || '新增失败')
    }
  } catch (error) {
    ElMessage.error('请求失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

// 删除分类
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
    // 取消删除或请求错误
    if (error !== 'cancel') {
      ElMessage.error('删除失败，网络异常')
    }
  }
}

onMounted(() => {
  fetchCategories()
})
</script>