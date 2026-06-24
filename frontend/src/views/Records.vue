
<template>
  <div>
    <h2>记账列表</h2>
    <el-table :data="records" border v-loading="loading">
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
          <el-button size="small" @click="deleteRecord(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-empty v-if="!loading && records.length === 0" description="暂无记录" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiGetAnalysis, apiDeleteRecord } from '@/api'   // 关键修改
import { ElMessage, ElMessageBox } from 'element-plus'

const records = ref([])
const loading = ref(false)

const fetchRecords = async () => {
  loading.value = true
  try {
    const month = new Date().toISOString().slice(0, 7)
    const res = await apiGetAnalysis(month)
    if (res.data.code === 200) {
      records.value = res.data.data.record_list || []
    }
  } catch (error) {
    ElMessage.error('获取记录失败')
  } finally {
    loading.value = false
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
  fetchRecords()
})
</script>