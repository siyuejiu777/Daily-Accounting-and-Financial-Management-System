<template>
  <div>
    <h2>仪表盘</h2>
    <el-row :gutter="20">
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>今日收入</template>
          <div style="font-size: 24px; color: #67C23A;">¥ 0.00</div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>今日支出</template>
          <div style="font-size: 24px; color: #F56C6C;">¥ 0.00</div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-card>
          <template #header>月度预算剩余</template>
          <div style="font-size: 24px; color: #409EFF;">¥ 0.00</div>
        </el-card>
      </el-col>
    </el-row>
    <el-card style="margin-top: 20px;">
      <template #header>快速记账</template>
      <el-input placeholder="搜索记账记录..." />
      <el-button type="primary" style="margin-top: 10px;">添加新记录</el-button>
    </el-card>
  </div>
</template>