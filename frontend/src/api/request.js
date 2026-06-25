// src/api/request.js
import axios from 'axios'

const request = axios.create({
  baseURL: 'http://localhost/finance_system/', // 注意结尾斜杠
  timeout: 5000,
  withCredentials: true  // 携带 Cookie，维持 Session
})

// 响应拦截器（可选，统一处理错误）
request.interceptors.response.use(
  response => response,
  error => {
    if (error.response) {
      // 例如 401 未登录，可以统一跳转到登录页
      if (error.response.status === 401) {
        // 清除本地状态并跳转
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default request