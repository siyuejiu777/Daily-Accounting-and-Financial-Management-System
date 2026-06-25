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

// 删除记录
export const apiDeleteRecord = (data) => {
  return request.post('record_delete.php', data)
  // data: { category_id, record_time }
}

// 后续如果A给了获取分类、获取记录等接口，在这里继续添加

export const apiGetCategories = () => {
  return request.get('categories_list.php') // 如果A用的文件名不同，请对齐
}

// 新增分类
export const apiAddCategory = (data) => {
  return request.post('category_add.php', data)
  // data: { category_name, type }
}

// 删除分类
export const apiDeleteCategory = (categoryId) => {
  return request.post('category_delete.php', { category_id: categoryId })
}

// 更新记录
export const apiUpdateRecord = (data) => {
  return request.post('record_update.php', data)
  // data: { category_id, old_record_time, amount, type, note, record_time }
}
