<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiLogin } from '@/api'

const router = useRouter()
const email = ref('')
const password = ref('')

const handleLogin = async () => {
  const res = await apiLogin({ email: email.value, password: password.value })
  localStorage.setItem('token', res.token)
  localStorage.setItem('user', JSON.stringify(res.user))
  router.push('/dashboard')  // 登录成功跳转到仪表盘
}
</script>