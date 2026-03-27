<template>
  <n-space vertical :size="24">
    <!-- Subscription info -->
    <n-card title="订阅信息">
      <n-spin :show="!userStore.info">
        <n-descriptions bordered :column="2">
          <n-descriptions-item label="邮箱">{{ userStore.info?.email || '-' }}</n-descriptions-item>
          <n-descriptions-item label="计费模式">
            <n-tag :type="userStore.isUsageMode ? 'info' : 'success'" size="small">
              {{ userStore.isUsageMode ? '按量计费' : '订阅模式' }}
            </n-tag>
          </n-descriptions-item>
          <n-descriptions-item label="当前套餐">{{ userStore.subscribe?.plan?.name || '未订阅' }}</n-descriptions-item>
          <n-descriptions-item label="到期时间">
            {{ userStore.subscribe?.expired_at ? new Date(userStore.subscribe.expired_at * 1000).toLocaleDateString() : '无' }}
          </n-descriptions-item>
          <n-descriptions-item label="钱包余额">¥{{ userStore.balanceYuan }}</n-descriptions-item>
          <n-descriptions-item label="已用流量">
            {{ formatBytes((userStore.subscribe?.u || 0) + (userStore.subscribe?.d || 0)) }}
            / {{ formatBytes(userStore.subscribe?.transfer_enable) }}
          </n-descriptions-item>
          <n-descriptions-item label="订阅链接" :span="2">
            <n-space align="center">
              <n-text code style="word-break: break-all; font-size: 12px">
                {{ userStore.subscribe?.subscribe_url || '-' }}
              </n-text>
              <n-button size="tiny" @click="copySubscribe" v-if="userStore.subscribe?.subscribe_url">复制</n-button>
              <n-button size="tiny" type="warning" @click="handleResetUrl">重置</n-button>
            </n-space>
          </n-descriptions-item>
        </n-descriptions>
      </n-spin>
    </n-card>

    <!-- Change password -->
    <n-card title="修改密码">
      <n-form ref="pwdFormRef" :model="pwdForm" :rules="pwdRules" label-placement="left" label-width="100">
        <n-form-item label="旧密码" path="old_password">
          <n-input v-model:value="pwdForm.old_password" type="password" show-password-on="click" placeholder="输入当前密码" />
        </n-form-item>
        <n-form-item label="新密码" path="new_password">
          <n-input v-model:value="pwdForm.new_password" type="password" show-password-on="click" placeholder="输入新密码" />
        </n-form-item>
        <n-form-item>
          <n-button type="primary" :loading="pwdLoading" @click="handleChangePassword">保存密码</n-button>
        </n-form-item>
      </n-form>
    </n-card>

    <!-- Notification preferences -->
    <n-card title="通知设置">
      <n-space vertical :size="12">
        <n-space justify="space-between" align="center">
          <span>到期邮件提醒</span>
          <n-switch :value="!!notifySettings.remind_expire" @update:value="v => updateNotify('remind_expire', v ? 1 : 0)" />
        </n-space>
        <n-space justify="space-between" align="center">
          <span>流量用尽邮件提醒</span>
          <n-switch :value="!!notifySettings.remind_traffic" @update:value="v => updateNotify('remind_traffic', v ? 1 : 0)" />
        </n-space>
      </n-space>
    </n-card>

    <!-- Telegram binding -->
    <n-card title="Telegram 绑定">
      <n-spin :show="tgLoading">
        <div v-if="tgBot">
          <n-alert type="info" style="margin-bottom: 12px">
            点击下方链接打开 Telegram Bot 完成绑定
          </n-alert>
          <n-button tag="a" :href="'https://t.me/' + tgBot.username + '?start=' + tgToken" target="_blank" type="primary">
            打开 @{{ tgBot.username }}
          </n-button>
        </div>
        <n-text v-else-if="!tgLoading" depth="3">Telegram Bot 未配置</n-text>
      </n-spin>
    </n-card>

    <!-- Security -->
    <n-card title="安全操作">
      <n-space>
        <n-popconfirm @positive-click="handleResetSecurity">
          <template #trigger>
            <n-button type="error">重置订阅链接和UUID</n-button>
          </template>
          确定要重置安全信息吗? 重置后需要重新导入订阅。
        </n-popconfirm>
      </n-space>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useMessage } from 'naive-ui'
import { useUserStore } from '@/stores/user'
import { changePassword, updateUser, resetSecurity, getTelegramBotInfo } from '@/api'

const userStore = useUserStore()
const message = useMessage()

// Password form
const pwdFormRef = ref(null)
const pwdLoading = ref(false)
const pwdForm = reactive({ old_password: '', new_password: '' })
const pwdRules = {
  old_password: { required: true, message: '请输入旧密码', trigger: 'blur' },
  new_password: { required: true, message: '请输入新密码', trigger: 'blur', min: 8 }
}

// Notification settings
const notifySettings = reactive({
  remind_expire: 0,
  remind_traffic: 0
})

// Telegram
const tgLoading = ref(false)
const tgBot = ref(null)
const tgToken = ref('')

async function handleChangePassword() {
  try {
    await pwdFormRef.value?.validate()
  } catch (e) { return }
  pwdLoading.value = true
  try {
    await changePassword(pwdForm)
    message.success('密码修改成功')
    pwdForm.old_password = ''
    pwdForm.new_password = ''
  } catch (e) {
    message.error(e.response?.data?.message || '修改失败')
  } finally {
    pwdLoading.value = false
  }
}

async function updateNotify(key, val) {
  notifySettings[key] = val
  try {
    await updateUser({ [key]: val })
    message.success('设置已更新')
  } catch (e) {
    notifySettings[key] = val ? 0 : 1
    message.error('更新失败')
  }
}

async function handleResetSecurity() {
  try {
    await resetSecurity()
    message.success('安全信息已重置')
    await userStore.fetchSubscribe()
  } catch (e) {
    message.error(e.response?.data?.message || '重置失败')
  }
}

async function handleResetUrl() {
  try {
    await resetSecurity()
    message.success('订阅链接已重置')
    await userStore.fetchSubscribe()
  } catch (e) {
    message.error(e.response?.data?.message || '重置失败')
  }
}

function copySubscribe() {
  const url = userStore.subscribe?.subscribe_url
  if (!url) return
  navigator.clipboard.writeText(url).then(() => {
    message.success('已复制到剪贴板')
  }).catch(() => {
    message.info('请手动复制')
  })
}

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const gb = bytes / (1024 * 1024 * 1024)
  if (gb >= 1) return gb.toFixed(2) + ' GB'
  const mb = bytes / (1024 * 1024)
  return mb.toFixed(2) + ' MB'
}

onMounted(async () => {
  await userStore.fetchInfo()
  userStore.fetchSubscribe()

  // Load notification prefs from user info
  if (userStore.info) {
    notifySettings.remind_expire = userStore.info.remind_expire || 0
    notifySettings.remind_traffic = userStore.info.remind_traffic || 0
    tgToken.value = userStore.info.telegram_token || userStore.info.uuid || ''
  }

  // Telegram bot info
  tgLoading.value = true
  try {
    const res = await getTelegramBotInfo()
    tgBot.value = res.data || null
  } catch (e) {}
  tgLoading.value = false
})
</script>
