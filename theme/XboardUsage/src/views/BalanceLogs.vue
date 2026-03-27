<template>
  <n-card title="余额明细">
    <n-alert type="info" style="margin-bottom: 16px">当前余额: ¥{{ userStore.balanceYuan }}</n-alert>
    <n-data-table :columns="columns" :data="logs" :loading="loading" :pagination="pagination" remote @update:page="handlePageChange" />
  </n-card>
</template>

<script setup>
import { ref, h, onMounted } from 'vue'
import { NTag } from 'naive-ui'
import { getBalanceLogs } from '@/api'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const logs = ref([])
const loading = ref(false)
const pagination = ref({ page: 1, pageSize: 20, itemCount: 0 })

const typeMap = {
  recharge: { label: '充值', type: 'success' },
  traffic_deduction: { label: '流量扣费', type: 'error' },
  package_purchase: { label: '流量包购买', type: 'warning' },
  refund: { label: '退款', type: 'info' },
  admin_adjust: { label: '管理员调整', type: 'default' },
  commission_transfer: { label: '佣金转入', type: 'success' }
}

const columns = [
  {
    title: '时间',
    key: 'created_at',
    render: row => new Date(row.created_at * 1000).toLocaleString()
  },
  {
    title: '类型',
    key: 'type',
    render: row => {
      const t = typeMap[row.type] || { label: row.type, type: 'default' }
      return h(NTag, { size: 'small', type: t.type }, { default: () => t.label })
    }
  },
  {
    title: '金额',
    key: 'amount',
    render: row => {
      const yuan = (row.amount / 100).toFixed(2)
      return h('span', { style: { color: row.amount > 0 ? '#18a058' : '#d03050' } }, row.amount > 0 ? `+¥${yuan}` : `¥${yuan}`)
    }
  },
  {
    title: '余额',
    key: 'balance_after',
    render: row => `¥${(row.balance_after / 100).toFixed(2)}`
  },
  { title: '描述', key: 'description', ellipsis: { tooltip: true } }
]

onMounted(() => loadLogs(1))

async function loadLogs(page) {
  loading.value = true
  try {
    const res = await getBalanceLogs({ page, limit: pagination.value.pageSize })
    const data = res.data
    logs.value = data.data || data
    pagination.value.itemCount = data.total || logs.value.length
    pagination.value.page = page
  } catch (e) {} finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  loadLogs(page)
}
</script>
