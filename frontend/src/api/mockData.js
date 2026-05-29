// src/api/mockData.js
export const mockCategories = [
  { id: 1, name: '餐饮', icon: 'food' },
  { id: 2, name: '交通', icon: 'transport' },
  { id: 3, name: '购物', icon: 'shopping' }
]

export const mockRecords = [
  { id: 1, amount: 30, type: 'expense', categoryId: 1, date: '2026-05-28', note: '午餐' },
  { id: 2, amount: 100, type: 'income', categoryId: null, date: '2026-05-28', note: '退款' }
]

export const mockDashboard = {
  todayIncome: 100.00,
  todayExpense: 30.00,
  monthlyBudgetRemain: 1970.00
}

export const mockLogin = {
  token: 'mock-token-abc',
  user: { id: 1, name: '测试用户' }
}