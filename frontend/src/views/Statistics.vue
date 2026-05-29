<template>
  <div>
    <h2>收支统计分析</h2>
    <el-card>
      <template #header>月度收支趋势</template>
      <div style="height: 300px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999;">
        图表将在这里显示（使用 ECharts）
      </div>
    </el-card>
    <el-card style="margin-top: 20px;">
      <template #header>支出分类占比</template>
      <div style="height: 300px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999;">
        饼图将在这里显示
      </div>
    </el-card>
  </div>
</template>