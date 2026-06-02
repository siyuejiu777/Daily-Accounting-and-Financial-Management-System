<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiLogin } from '@/api'
import { ElMessage } from 'element-plus'

const router = useRouter()
const username = ref('')
const password = ref('')

const handleLogin = async () => {
  try {
    const res = await apiLogin(username.value, password.value)
    if (res.data.code === 200) {
      // 登录成功，存储用户名
      localStorage.setItem('user', JSON.stringify({ name: res.data.data.username }))
      localStorage.setItem('token', 'php-session') // 标记已登录（实际依靠Cookie）
      ElMessage.success(res.data.msg)
      router.push('/dashboard')
    } else {
      ElMessage.error(res.data.msg)
    }
  } catch (error) {
    ElMessage.error('网络错误或服务器异常')
  }
}
</script>