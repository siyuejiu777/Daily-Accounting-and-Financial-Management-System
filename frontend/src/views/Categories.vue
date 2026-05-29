<template>
  <div>
    <h2>收支分类管理</h2>
    <el-button type="primary" style="margin-bottom: 20px;">新增分类</el-button>
    <el-table :data="[]" border>
      <el-table-column prop="name" label="分类名称" />
      <el-table-column prop="type" label="类型" />
      <el-table-column label="操作">
        <template #default>
          <el-button size="small">编辑</el-button>
          <el-button size="small" type="danger">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-empty description="暂无分类数据" />
  </div>
</template>