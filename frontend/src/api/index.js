// src/api/index.js
import request from './request'

// 用户注册
export const apiRegister = (username, password) => {
  return request.post('register.php', { username, password })
}

// 用户登录
export const apiLogin = (username, password) => {
  return request.post('login.php', { username, password })
}

// 添加账目
export const apiAddRecord = (data) => {
  return request.post('record_add.php', data)
}

// 设置月度预算
export const apiSetBudget = (budget_month, amount) => {
  return request.post('budget_set.php', { budget_month, amount })
}

// 获取统计分析数据
export const apiGetAnalysis = (month) => {
  return request.get('analysis.php', { params: { month } })
}

// 后续如果A给了获取分类、获取记录等接口，在这里继续添加