<template>
  <el-container class="app-container">
    <!-- 侧边导航栏 -->
    <el-aside :width="isCollapse ? '64px' : '200px'" class="aside">
      <div class="logo" @click="toggleCollapse">
        <span v-show="!isCollapse">💰 记账管家</span>
        <span v-show="isCollapse">💰</span>
      </div>
      <el-menu
        :default-active="activeMenu"
        router
        :collapse="isCollapse"
        background-color="#304156"
        text-color="#bfcbd9"
        active-text-color="#409EFF"
      >
        <el-menu-item index="/dashboard">
          <el-icon><DataBoard /></el-icon>
          <span>仪表盘</span>
        </el-menu-item>
        <el-menu-item index="/categories">
          <el-icon><Collection /></el-icon>
          <span>分类管理</span>
        </el-menu-item>
        <el-menu-item index="/records">
          <el-icon><List /></el-icon>
          <span>记账列表</span>
        </el-menu-item>
        <el-menu-item index="/statistics">
          <el-icon><TrendCharts /></el-icon>
          <span>统计分析</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <!-- 右侧主内容区 -->
    <el-container>
      <el-header class="header">
        <div class="header-left">
          <el-button @click="toggleCollapse" :icon="isCollapse ? 'Expand' : 'Fold'" circle />
        </div>
        <div class="header-right">
          <span>用户: {{ userName }}</span>
          <el-button type="danger" @click="handleLogout" size="small" style="margin-left: 20px;">退出</el-button>
        </div>
      </el-header>
      <el-main class="main-content">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { DataBoard, Collection, List, TrendCharts } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const isCollapse = ref(false)
const userName = ref('')

// 获取当前激活的菜单项
const activeMenu = computed(() => route.path)

// 从 localStorage 读取用户名
onMounted(() => {
  const user = localStorage.getItem('user')
  if (user) {
    try {
      userName.value = JSON.parse(user).name || '用户'
    } catch (e) {
      userName.value = '用户'
    }
  }
})

const toggleCollapse = () => {
  isCollapse.value = !isCollapse.value
}

// App.vue 中 handleLogout 修改为
const handleLogout = () => {
  localStorage.removeItem('isLoggedIn')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<style scoped>
.app-container {
  height: 100vh;
}

.aside {
  background-color: #304156;
  transition: width 0.3s;
  overflow-x: hidden;
}

.logo {
  height: 60px;
  line-height: 60px;
  text-align: center;
  color: #fff;
  font-size: 18px;
  cursor: pointer;
  user-select: none;
}

.header {
  background: #fff;
  border-bottom: 1px solid #e6e6e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 20px;
}

.header-left {
  display: flex;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #666;
}

.main-content {
  background: #f0f2f5;
  min-height: calc(100vh - 60px);
}
</style>