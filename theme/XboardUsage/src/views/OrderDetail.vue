<template>
  <n-space vertical :size="24">
    <n-spin :show="loading">
      <n-card title="订单详情">
        <template #header-extra>
          <n-tag :type="statusType" size="medium">{{ statusLabel }}</n-tag>
        </template>

        <n-descriptions bordered :column="2">
          <n-descriptions-item label="订单号">{{ order.trade_no }}</n-descriptions-item>
          <n-descriptions-item label="套餐名称">{{ order.plan?.name || '-' }}</n-descriptions-item>
          <n-descriptions-item label="付费周期">{{ periodLabels[order.period] || order.period || '-' }}</n-descriptions-item>
          <n-descriptions-item label="订单金额">¥{{ ((order.total_amount || 0) / 100).toFixed(2) }}</n-descriptions-item>
          <n-descriptions-item label="优惠金额" v-if="order.discount_amount">
            -¥{{ (order.discount_amount / 100).toFixed(2) }}
          </n-descriptions-item>
          <n-descriptions-item label="退款金额" v-if="order.surplus_amount">
            ¥{{ (order.surplus_amount / 100).toFixed(2) }}
          </n-descriptions-item>
          <n-descriptions-item label="创建时间">
            {{ order.created_at ? new Date(order.created_at * 1000).toLocaleString() : '-' }}
          </n-descriptions-item>
          <n-descriptions-item label="支付时间" v-if="order.paid_at">
            {{ new Date(order.paid_at * 1000).toLocaleString() }}
          </n-descriptions-item>
        </n-descriptions>
      </n-card>

      <!-- Pending order: show payment options -->
      <n-card title="选择支付方式" v-if="order.status === 0">
        <n-radio-group v-model:value="paymentMethod" style="width: 100%">
          <n-space vertical>
            <n-radio v-for="m in methods" :key="m.id" :value="m.id" style="width: 100%">
              {{ m.name }}
              <n-tag v-if="m.handling_fee_percent" size="tiny" type="warning" style="margin-left: 8px">
                +{{ m.handling_fee_percent }}%
              </n-tag>
            </n-radio>
          </n-space>
        </n-radio-group>

        <n-divider />

        <n-space>
          <n-button type="primary" size="large" :loading="payLoading" :disabled="!paymentMethod" @click="handleCheckout">
            立即支付
          </n-button>
          <n-button @click="handleCancel" :loading="cancelLoading">取消订单</n-button>
        </n-space>
      </n-card>
    </n-spin>

    <n-button @click="$router.push('/order')" quaternary>
      &larr; 返回订单列表
    </n-button>
  </n-space>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage } from 'naive-ui'
import { getOrderDetail, getPaymentMethods, checkoutOrder, cancelOrder, checkOrder } from '@/api'

const props = defineProps({ tradeNo: String })
const router = useRouter()
const message = useMessage()

const loading = ref(false)
const order = ref({})
const methods = ref([])
const paymentMethod = ref(null)
const payLoading = ref(false)
const cancelLoading = ref(false)
let pollTimer = null

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

const statusLabel = computed(() => (statusMap[order.value.status] || { label: '未知' }).label)
const statusType = computed(() => (statusMap[order.value.status] || { type: 'default' }).type)

async function fetchOrder() {
  loading.value = true
  try {
    const res = await getOrderDetail({ trade_no: props.tradeNo })
    order.value = res.data || {}
  } catch (e) {
    message.error('获取订单详情失败')
  } finally {
    loading.value = false
  }
}

async function fetchMethods() {
  try {
    const res = await getPaymentMethods()
    methods.value = res.data || []
    if (methods.value.length) paymentMethod.value = methods.value[0].id
  } catch (e) {}
}

async function handleCheckout() {
  payLoading.value = true
  try {
    const res = await checkoutOrder({ trade_no: props.tradeNo, method: paymentMethod.value })
    const data = res.data
    if (data?.type === -1) {
      message.success('支付成功')
      await fetchOrder()
    } else if (data?.data) {
      window.open(data.data, '_blank')
      startPolling()
    } else {
      message.success('操作完成')
      await fetchOrder()
    }
  } catch (e) {
    message.error(e.response?.data?.message || '支付失败')
  } finally {
    payLoading.value = false
  }
}

async function handleCancel() {
  cancelLoading.value = true
  try {
    await cancelOrder({ trade_no: props.tradeNo })
    message.success('订单已取消')
    await fetchOrder()
  } catch (e) {
    message.error(e.response?.data?.message || '取消失败')
  } finally {
    cancelLoading.value = false
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(async () => {
    try {
      const res = await checkOrder({ trade_no: props.tradeNo })
      if (res.data && res.data !== 0) {
        stopPolling()
        message.success('支付成功')
        await fetchOrder()
      }
    } catch (e) {}
  }, 3000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(() => {
  fetchOrder()
  fetchMethods()
})

onUnmounted(() => stopPolling())
</script>
