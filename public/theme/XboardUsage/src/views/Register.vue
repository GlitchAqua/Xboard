<template>
  <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f0f2f5">
    <n-card style="width: 400px" title="注册">
      <n-form ref="formRef" :model="form" :rules="rules">
        <n-form-item label="用户名" path="username">
          <n-input v-model:value="form.username" placeholder="请输入用户名（字母、数字、下划线）" />
        </n-form-item>
        <n-form-item label="密码" path="password">
          <n-input v-model:value="form.password" type="password" placeholder="请输入密码（至少8位）" show-password-on="click" />
        </n-form-item>
        <n-form-item label="邮箱（选填）" path="email">
          <n-input v-model:value="form.email" placeholder="可稍后在个人中心绑定" />
        </n-form-item>
        <n-form-item v-if="guestConfig?.is_email_verify && form.email" label="验证码" path="email_code">
          <n-input-group>
            <n-input v-model:value="form.email_code" placeholder="邮箱验证码" />
            <n-button @click="sendVerify" :disabled="countdown > 0">
              {{ countdown > 0 ? countdown + 's' : '发送' }}
            </n-button>
          </n-input-group>
        </n-form-item>
        <n-form-item v-if="guestConfig?.is_invite_force" label="邀请码" path="invite_code">
          <n-input v-model:value="form.invite_code" placeholder="邀请码" />
        </n-form-item>
        <n-button type="primary" block :loading="loading" @click="handleRegister">注册</n-button>
      </n-form>
      <template #footer>
        <n-space justify="center">
          <router-link to="/login">已有账号？立即登录</router-link>
        </n-space>
      </template>
    </n-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage } from 'naive-ui'
import { register, getGuestConfig, sendEmailVerify } from '@/api'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const message = useMessage()
const userStore = useUserStore()
const loading = ref(false)
const countdown = ref(0)
const guestConfig = ref(null)
const form = ref({ username: '', email: '', password: '', email_code: '', invite_code: '' })
const rules = {
  username: { required: true, message: '请输入用户名', trigger: 'blur' },
  password: { required: true, message: '请输入密码', trigger: 'blur' }
}

onMounted(async () => {
  try {
    const res = await getGuestConfig()
    guestConfig.value = res.data
  } catch (e) {}
})

async function sendVerify() {
  try {
    await sendEmailVerify({ email: form.value.email })
    message.success('验证码已发送')
    countdown.value = 60
    const t = setInterval(() => { if (--countdown.value <= 0) clearInterval(t) }, 1000)
  } catch (e) {
    message.error(e.message || '发送失败')
  }
}

async function handleRegister() {
  loading.value = true
  try {
    const res = await register(form.value)
    const token = res.data?.auth_data
    if (token) {
      userStore.setToken(token)
      router.push('/')
    }
  } catch (e) {
    message.error(e.message || '注册失败')
  } finally {
    loading.value = false
  }
}
</script>
