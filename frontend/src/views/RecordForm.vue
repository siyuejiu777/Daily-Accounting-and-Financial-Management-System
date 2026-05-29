<template>
  <div>
    <h2>记账列表</h2>
    <el-form :inline="true">
      <el-form-item label="分类筛选">
        <el-select placeholder="全部" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary">查询</el-button>
      </el-form-item>
    </el-form>
    <el-table :data="[]" border>
      <el-table-column prop="amount" label="金额" />
      <el-table-column prop="type" label="类型" />
      <el-table-column prop="categoryName" label="分类" />
      <el-table-column prop="date" label="日期" />
      <el-table-column prop="note" label="备注" />
      <el-table-column label="操作">
        <template #default>
          <el-button size="small">编辑</el-button>
          <el-button size="small" type="danger">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-empty description="暂无记录" />
  </div>
</template>