import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  timeout: 15000,
  headers: { 'Content-Type': 'application/json' }
})

http.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = token
  }
  return config
})

http.interceptors.response.use(
  res => {
    if (res.data.status === 'success' || res.data.data !== undefined) {
      return res.data
    }
    return Promise.reject(res.data)
  },
  err => {
    if (err.response?.status === 401 || err.response?.status === 403) {
      localStorage.removeItem('token')
      window.location.hash = '#/login'
    }
    const msg = err.response?.data?.message || err.message || '请求失败'
    return Promise.reject({ message: msg })
  }
)

export default http
