import { defineStore } from 'pinia'
import { getUserInfo, getUserStat, getSubscribe, checkLogin } from '@/api'

export const useUserStore = defineStore('user', {
  state: () => ({
    info: null,
    stat: null,
    subscribe: null,
    isLogin: false
  }),

  getters: {
    billingMode: s => s.info?.billing_mode || window.settings?.billing_mode || 'subscription',
    isUsageMode: s => (s.info?.billing_mode || window.settings?.billing_mode) === 'usage',
    balance: s => s.info?.balance ?? 0,
    balanceYuan: s => ((s.info?.balance ?? 0) / 100).toFixed(2),
    billingStatus: s => s.info?.billing_status || null
  },

  actions: {
    async fetchInfo() {
      try {
        const res = await getUserInfo()
        this.info = res.data
        return res.data
      } catch (e) {
        return null
      }
    },
    async fetchStat() {
      try {
        const res = await getUserStat()
        this.stat = res.data
      } catch (e) {}
    },
    async fetchSubscribe() {
      try {
        const res = await getSubscribe()
        this.subscribe = res.data
      } catch (e) {}
    },
    async checkAuth() {
      try {
        const res = await checkLogin()
        this.isLogin = res.data?.is_login || false
        return this.isLogin
      } catch (e) {
        this.isLogin = false
        return false
      }
    },
    setToken(token) {
      localStorage.setItem('token', token)
      this.isLogin = true
    },
    logout() {
      localStorage.removeItem('token')
      this.isLogin = false
      this.info = null
      this.stat = null
      this.subscribe = null
    }
  }
})
