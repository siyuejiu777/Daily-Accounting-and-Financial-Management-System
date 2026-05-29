<template>
  <div>
    <h2>{{ isEdit ? '编辑记录' : '添加记录' }}</h2>
    <el-form label-width="80px" style="max-width: 500px;">
      <el-form-item label="金额">
        <el-input-number :min="0" :precision="2" />
      </el-form-item>
      <el-form-item label="类型">
        <el-radio-group>
          <el-radio value="income">收入</el-radio>
          <el-radio value="expense">支出</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="分类">
        <el-select placeholder="请选择分类" />
      </el-form-item>
      <el-form-item label="日期">
        <el-date-picker type="date" placeholder="选择日期" />
      </el-form-item>
      <el-form-item label="备注">
        <el-input type="textarea" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary">保存</el-button>
        <el-button>取消</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const isEdit = ref(false)
</script>