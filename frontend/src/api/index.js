// src/api/index.js
import { mockCategories, mockRecords, mockDashboard, mockLogin } from './mockData'

// 临时使用 Mock 数据，后续替换为真实 axios 请求
export const apiLogin = (credentials) => {
  return new Promise((resolve) => {
    console.log('Mock login with', credentials)
    setTimeout(() => resolve(mockLogin), 300)
  })
}

export const apiGetCategories = () => {
  return new Promise((resolve) => {
    setTimeout(() => resolve(mockCategories), 200)
  })
}

export const apiGetRecords = (categoryId) => {
  return new Promise((resolve) => {
    const filtered = categoryId
      ? mockRecords.filter(r => r.categoryId == categoryId)
      : mockRecords
    setTimeout(() => resolve(filtered), 200)
  })
}

export const apiGetDashboard = () => {
  return new Promise((resolve) => {
    setTimeout(() => resolve(mockDashboard), 200)
  })
}

// 其他接口函数（添加、删除、修改等）可以后续补充