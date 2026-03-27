<template>
  <n-space vertical :size="24">
    <n-card title="我的订单">
      <n-data-table
        :columns="columns"
        :data="orders"
        :loading="loading"
        :pagination="pagination"
        :row-key="row => row.trade_no"
        @update:page="handlePageChange"
        remote
      />
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, h, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { NTag, NButton } from 'naive-ui'
import { getOrders } from '@/api'

const router = useRouter()
const loading = ref(false)
const orders = ref([])
const pagination = reactive({ page: 1, pageSize: 15, itemCount: 0 })

const statusMap = {
  0: { label: '待支付', type: 'warning' },
  1: { label: '开通中', type: 'info' },
  2: { label: '已取消', type: 'default' },
  3: { label: '已完成', type: 'success' },
  4: { label: '已折抵', type: 'info' }
}

const periodLabels = {
  month_price: '月付',
  quarter_price: '季付',
  half_year_price: '半年付',
  year_price: '年付',
  two_year_price: '两年付',
  three_year_price: '三年付',
  onetime_price: '一次性',
  reset_price: '重置流量',
  recharge: '充值'
}

const columns = [
  { title: '订单号', key: 'trade_no', ellipsis: { tooltip: true } },
  {
    title: '类型',
    key: 'period',
    render: row => periodLabels[row.period] || row.period || '-'
  },
  {
    title: '金额',
    key: 'total_amount',
    render: row => '¥' + ((row.total_amount || 0) / 100).toFixed(2)
  },
  {
    title: '状态',
    key: 'status',
    render: row => {
      const s = statusMap[row.status] || { label: '未知', type: 'default' }
      return h(NTag, { type: s.type, size: 'small' }, () => s.label)
    }
  },
  {
    title: '创建时间',
    key: 'created_at',
    render: row => row.created_at ? new Date(row.created_at * 1000).toLocaleString() : '-'
  },
  {
    title: '操作',
    key: 'actions',
    render: row => h(NButton, {
      text: true,
      type: 'primary',
      onClick: () => router.push(`/order/${row.trade_no}`)
    }, () => '查看')
  }
]

async function fetchOrders() {
  loading.value = true
  try {
    const res = await getOrders({ page: pagination.page })
    const data = res.data
    orders.value = data?.data || data || []
    pagination.itemCount = data?.total || orders.value.length
  } catch (e) {
    orders.value = []
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.page = page
  fetchOrders()
}

onMounted(() => fetchOrders())
</script>
