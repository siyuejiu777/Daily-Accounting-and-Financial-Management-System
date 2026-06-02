import axios from 'axios'

const request = axios.create({
  baseURL: 'http://localhost/finance_system/', // 成员A给的基准路径
  timeout: 5000,
  withCredentials: true // 关键！携带 Session Cookie，否则后端会返回401
})

export default request

// src/api/index.js
import request from './request' // 上面刚创建的 axios 实例

// ==================== 用户认证 ====================
export const apiRegister = (username, password) => {
  return request.post('register.php', { username, password })
}

export const apiLogin = (username, password) => {
  return request.post('login.php', { username, password })
}

// ==================== 记账与预算 ====================
export const apiAddRecord = (data) => {
  return request.post('record_add.php', data)
  // data 格式：{ amount, category_id, remark, record_date }
}

export const apiSetBudget = (budget_month, amount) => {
  return request.post('budget_set.php', { budget_month, amount })
}

// ==================== 统计分析 ====================
export const apiGetAnalysis = (month) => {
  return request.get('analysis.php', { params: { month } })
}