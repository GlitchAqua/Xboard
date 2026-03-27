<template>
  <n-space vertical :size="24">
    <!-- Invite codes -->
    <n-card title="邀请推广">
      <template #header-extra>
        <n-button type="primary" size="small" :loading="generating" @click="handleGenerate">生成邀请码</n-button>
      </template>

      <n-spin :show="loading">
        <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive style="margin-bottom: 20px">
          <n-gi span="3 m:1">
            <n-statistic label="已注册用户" :value="inviteData.stat?.[0] || 0" />
          </n-gi>
          <n-gi span="3 m:1">
            <n-statistic label="佣金比例">
              {{ (inviteData.stat?.[1] || 0) }}%
            </n-statistic>
          </n-gi>
          <n-gi span="3 m:1">
            <n-statistic label="确认佣金">
              <template #prefix>¥</template>
              {{ ((inviteData.stat?.[2] || 0) / 100).toFixed(2) }}
            </n-statistic>
          </n-gi>
        </n-grid>

        <n-divider>邀请码列表</n-divider>

        <n-list bordered v-if="codes.length">
          <n-list-item v-for="code in codes" :key="code.id">
            <n-space justify="space-between" align="center" style="width: 100%">
              <n-text code>{{ inviteUrl(code.code) }}</n-text>
              <n-button size="tiny" @click="copyLink(code.code)">复制</n-button>
            </n-space>
          </n-list-item>
        </n-list>
        <n-empty v-if="!loading && !codes.length" description="暂无邀请码，点击上方按钮生成" />
      </n-spin>
    </n-card>

    <!-- Commission details -->
    <n-card title="佣金明细">
      <n-data-table
        :columns="detailColumns"
        :data="details"
        :loading="detailLoading"
        :row-key="row => row.id"
        :pagination="pagination"
        remote
        @update:page="handlePageChange"
        size="small"
      />
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, h, reactive, onMounted } from 'vue'
import { useMessage, NTag } from 'naive-ui'
import { getInvite, saveInvite, getInviteDetails } from '@/api'

const message = useMessage()

const loading = ref(false)
const generating = ref(false)
const inviteData = ref({})
const codes = ref([])
const details = ref([])
const detailLoading = ref(false)
const pagination = reactive({ page: 1, pageSize: 15, itemCount: 0 })

const statusMap = {
  0: { label: '待确认', type: 'warning' },
  1: { label: '确认有效', type: 'info' },
  2: { label: '佣金已发放', type: 'success' },
  3: { label: '已作废', type: 'error' }
}

const detailColumns = [
  {
    title: '订单号',
    key: 'trade_no',
    ellipsis: { tooltip: true }
  },
  {
    title: '佣金金额',
    key: 'commission_balance',
    render: row => '¥' + ((row.commission_balance || 0) / 100).toFixed(2)
  },
  {
    title: '状态',
    key: 'commission_status',
    render: row => {
      const s = statusMap[row.commission_status] || { label: '未知', type: 'default' }
      return h(NTag, { type: s.type, size: 'small' }, () => s.label)
    }
  },
  {
    title: '时间',
    key: 'created_at',
    render: row => row.created_at ? new Date(row.created_at * 1000).toLocaleString() : '-'
  }
]

function inviteUrl(code) {
  return `${window.location.origin}/#/register?code=${code}`
}

function copyLink(code) {
  const url = inviteUrl(code)
  navigator.clipboard.writeText(url).then(() => {
    message.success('已复制到剪贴板')
  }).catch(() => {
    message.info('请手动复制链接')
  })
}

async function handleGenerate() {
  generating.value = true
  try {
    await saveInvite()
    message.success('邀请码已生成')
    await fetchInvite()
  } catch (e) {
    message.error(e.response?.data?.message || '生成失败')
  } finally {
    generating.value = false
  }
}

async function fetchInvite() {
  loading.value = true
  try {
    const res = await getInvite()
    const data = res.data || {}
    inviteData.value = data
    codes.value = data.codes || []
  } catch (e) {}
  loading.value = false
}

async function fetchDetails() {
  detailLoading.value = true
  try {
    const res = await getInviteDetails({ page: pagination.page })
    const data = res.data
    details.value = data?.data || data || []
    pagination.itemCount = data?.total || details.value.length
  } catch (e) {
    details.value = []
  }
  detailLoading.value = false
}

function handlePageChange(page) {
  pagination.page = page
  fetchDetails()
}

onMounted(() => {
  fetchInvite()
  fetchDetails()
})
</script>
