<template>
  <n-space vertical :size="24">
    <n-card :title="ticket.subject || '工单详情'">
      <template #header-extra>
        <n-space>
          <n-tag :type="ticket.status === 0 ? 'success' : 'default'" size="small">
            {{ ticket.status === 0 ? '已开启' : '已关闭' }}
          </n-tag>
          <n-button v-if="ticket.status === 0" size="small" @click="handleClose" :loading="closeLoading">
            关闭工单
          </n-button>
        </n-space>
      </template>

      <n-spin :show="loading">
        <n-list bordered>
          <n-list-item v-for="msg in messages" :key="msg.id">
            <div :style="{ textAlign: msg.is_me ? 'right' : 'left' }">
              <n-tag :type="msg.is_me ? 'primary' : 'info'" size="small" style="margin-bottom: 4px">
                {{ msg.is_me ? '我' : '客服' }}
              </n-tag>
              <div style="white-space: pre-wrap; padding: 8px 0">{{ msg.message }}</div>
              <n-text depth="3" style="font-size: 12px">
                {{ msg.created_at ? new Date(msg.created_at * 1000).toLocaleString() : '' }}
              </n-text>
            </div>
          </n-list-item>
          <n-list-item v-if="!messages.length && !loading">
            <n-empty description="暂无消息" />
          </n-list-item>
        </n-list>
      </n-spin>
    </n-card>

    <n-card v-if="ticket.status === 0">
      <n-input
        v-model:value="replyText"
        type="textarea"
        :rows="4"
        placeholder="输入回复内容..."
      />
      <n-space justify="end" style="margin-top: 12px">
        <n-button type="primary" :loading="replyLoading" :disabled="!replyText.trim()" @click="handleReply">
          发送回复
        </n-button>
      </n-space>
    </n-card>

    <n-button @click="$router.push('/ticket')" quaternary>
      &larr; 返回工单列表
    </n-button>
  </n-space>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useMessage } from 'naive-ui'
import { getTickets, replyTicket, closeTicket } from '@/api'

const props = defineProps({ id: [String, Number] })
const message = useMessage()

const loading = ref(false)
const ticket = ref({})
const messages = ref([])
const replyText = ref('')
const replyLoading = ref(false)
const closeLoading = ref(false)

async function fetchTicket() {
  loading.value = true
  try {
    const res = await getTickets({ id: props.id })
    const data = res.data
    if (Array.isArray(data)) {
      ticket.value = data[0] || {}
      messages.value = data[0]?.message || []
    } else {
      ticket.value = data || {}
      messages.value = data?.message || []
    }
  } catch (e) {
    message.error('获取工单详情失败')
  } finally {
    loading.value = false
  }
}

async function handleReply() {
  if (!replyText.value.trim()) return
  replyLoading.value = true
  try {
    await replyTicket({ id: Number(props.id), message: replyText.value })
    message.success('回复成功')
    replyText.value = ''
    await fetchTicket()
  } catch (e) {
    message.error(e.response?.data?.message || '回复失败')
  } finally {
    replyLoading.value = false
  }
}

async function handleClose() {
  closeLoading.value = true
  try {
    await closeTicket({ id: Number(props.id) })
    message.success('工单已关闭')
    await fetchTicket()
  } catch (e) {
    message.error(e.response?.data?.message || '关闭失败')
  } finally {
    closeLoading.value = false
  }
}

onMounted(() => fetchTicket())
</script>
