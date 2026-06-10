<template>
  <div class="login-container">
    <el-card class="login-card">
      <h2>记账理财</h2>
      <el-form v-if="!isRegister" :model="loginForm" label-width="0">
        <el-form-item>
          <el-input v-model="loginForm.username" placeholder="用户名" prefix-icon="User" />
        </el-form-item>
        <el-form-item>
          <el-input v-model="loginForm.password" type="password" placeholder="密码" prefix-icon="Lock" show-password />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleLogin" :loading="loading" style="width:100%">登录</el-button>
        </el-form-item>
        <div style="text-align:right">
          <el-button type="text" @click="isRegister = true">去注册</el-button>
        </div>
      </el-form>

      <el-form v-else :model="registerForm" label-width="0">
        <el-form-item>
          <el-input v-model="registerForm.username" placeholder="用户名" />
        </el-form-item>
        <el-form-item>
          <el-input v-model="registerForm.password" type="password" placeholder="密码" show-password />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleRegister" :loading="loading" style="width:100%">注册</el-button>
        </el-form-item>
        <div style="text-align:right">
          <el-button type="text" @click="isRegister = false">去登录</el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { apiLogin, apiRegister } from '@/api'
import { ElMessage } from 'element-plus'

const router = useRouter()
const loading = ref(false)
const isRegister = ref(false)

const loginForm = reactive({ username: '', password: '' })
const registerForm = reactive({ username: '', password: '' })

const handleLogin = async () => {
  if (!loginForm.username || !loginForm.password) {
    ElMessage.warning('请输入用户名和密码')
    return
  }
  loading.value = true
  try {
    const res = await apiLogin(loginForm.username, loginForm.password)
    if (res.data.code === 200) {
      localStorage.setItem('user', JSON.stringify({ name: res.data.data.username }))
      localStorage.setItem('isLoggedIn', 'true') // 简单标记已登录
      ElMessage.success(res.data.msg)
      router.push('/dashboard')
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('登录失败，服务器异常')
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  if (!registerForm.username || !registerForm.password) {
    ElMessage.warning('请输入用户名和密码')
    return
  }
  loading.value = true
  try {
    const res = await apiRegister(registerForm.username, registerForm.password)
    if (res.data.code === 200) {
      ElMessage.success(res.data.msg)
      // 注册成功后切换到登录
      isRegister.value = false
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('注册失败，服务器异常')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: #f0f2f5;
}
.login-card {
  width: 350px;
}
.login-card h2 {
  text-align: center;
  margin-bottom: 20px;
}
</style>