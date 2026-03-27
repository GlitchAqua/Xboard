<template>
  <n-space vertical :size="24">
    <n-card title="我的工单">
      <template #header-extra>
        <n-button type="primary" size="small" @click="showCreate = true">新建工单</n-button>
      </template>

      <n-data-table
        :columns="columns"
        :data="tickets"
        :loading="loading"
        :row-key="row => row.id"
        size="small"
      />
      <n-empty v-if="!loading && !tickets.length" description="暂无工单" />
    </n-card>

    <n-modal v-model:show="showCreate" preset="dialog" title="新建工单" positive-text="提交" negative-text="取消"
      :loading="createLoading" @positive-click="handleCreate" style="width: 500px">
      <n-form ref="formRef" :model="form" :rules="rules" label-placement="top">
        <n-form-item label="主题" path="subject">
          <n-input v-model:value="form.subject" placeholder="请输入工单主题" />
        </n-form-item>
        <n-form-item label="优先级" path="level">
          <n-select v-model:value="form.level" :options="levelOptions" />
        </n-form-item>
        <n-form-item label="内容" path="message">
          <n-input v-model:value="form.message" type="textarea" :rows="5" placeholder="详细描述您的问题" />
        </n-form-item>
      </n-form>
    </n-modal>
  </n-space>
</template>

<script setup>
import { ref, h, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage, NTag, NButton } from 'naive-ui'
import { getTickets, createTicket } from '@/api'

const router = useRouter()
const message = useMessage()

const loading = ref(false)
const tickets = ref([])
const showCreate = ref(false)
const createLoading = ref(false)
const formRef = ref(null)

const form = ref({ subject: '', level: 1, message: '' })
const rules = {
  subject: { required: true, message: '请输入主题', trigger: 'blur' },
  message: { required: true, message: '请输入内容', trigger: 'blur' }
}

const levelOptions = [
  { label: '低', value: 0 },
  { label: '中', value: 1 },
  { label: '高', value: 2 }
]

const statusMap = {
  0: { label: '已开启', type: 'success' },
  1: { label: '已关闭', type: 'default' }
}

const levelMap = { 0: '低', 1: '中', 2: '高' }

const columns = [
  { title: '主题', key: 'subject', ellipsis: { tooltip: true } },
  {
    title: '优先级',
    key: 'level',
    width: 80,
    render: row => levelMap[row.level] || '-'
  },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: row => {
      const s = statusMap[row.status] || { label: '未知', type: 'default' }
      return h(NTag, { type: s.type, size: 'small' }, () => s.label)
    }
  },
  {
    title: '最后回复',
    key: 'updated_at',
    render: row => row.updated_at ? new Date(row.updated_at * 1000).toLocaleString() : '-'
  },
  {
    title: '创建时间',
    key: 'created_at',
    render: row => row.created_at ? new Date(row.created_at * 1000).toLocaleString() : '-'
  },
  {
    title: '操作',
    key: 'actions',
    width: 80,
    render: row => h(NButton, {
      text: true,
      type: 'primary',
      onClick: () => router.push(`/ticket/${row.id}`)
    }, () => '查看')
  }
]

async function fetchTickets() {
  loading.value = true
  try {
    const res = await getTickets()
    tickets.value = res.data?.data || res.data || []
  } catch (e) {
    tickets.value = []
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  try {
    await formRef.value?.validate()
  } catch (e) {
    return false
  }
  createLoading.value = true
  try {
    await createTicket(form.value)
    message.success('工单创建成功')
    showCreate.value = false
    form.value = { subject: '', level: 1, message: '' }
    await fetchTickets()
  } catch (e) {
    message.error(e.response?.data?.message || '创建失败')
    return false
  } finally {
    createLoading.value = false
  }
}

onMounted(() => fetchTickets())
</script>
