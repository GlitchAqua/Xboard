<template>
  <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f0f2f5">
    <n-card style="width: 400px" :title="appTitle">
      <n-form ref="formRef" :model="form" :rules="rules">
        <n-form-item label="用户名 / 邮箱" path="email">
          <n-input v-model:value="form.email" placeholder="请输入用户名或邮箱" />
        </n-form-item>
        <n-form-item label="密码" path="password">
          <n-input v-model:value="form.password" type="password" placeholder="请输入密码" show-password-on="click" />
        </n-form-item>
        <n-button type="primary" block :loading="loading" @click="handleLogin">登录</n-button>
      </n-form>
      <template #footer>
        <n-space justify="center">
          <router-link to="/register">还没有账号？立即注册</router-link>
        </n-space>
      </template>
    </n-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage } from 'naive-ui'
import { login } from '@/api'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const message = useMessage()
const userStore = useUserStore()
const appTitle = window.settings?.title || 'Xboard'
const loading = ref(false)
const form = ref({ email: '', password: '' })
const rules = {
  email: { required: true, message: '请输入用户名或邮箱', trigger: 'blur' },
  password: { required: true, message: '请输入密码', trigger: 'blur' }
}

async function handleLogin() {
  loading.value = true
  try {
    const res = await login(form.value)
    const token = res.data?.auth_data
    if (token) {
      userStore.setToken(token)
      router.push('/')
    }
  } catch (e) {
    message.error(e.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>
