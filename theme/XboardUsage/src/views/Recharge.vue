<template>
  <n-space vertical :size="24">
    <n-card title="充值额度">
      <n-alert type="info" style="margin-bottom: 16px">
        当前余额: ¥{{ userStore.balanceYuan }}
      </n-alert>

      <n-form-item label="选择充值金额">
        <n-space>
          <n-button v-for="a in presetAmounts" :key="a" :type="amount === a ? 'primary' : 'default'" @click="amount = a">
            ¥{{ (a / 100).toFixed(0) }}
          </n-button>
        </n-space>
      </n-form-item>

      <n-form-item label="自定义金额 (元)">
        <n-input-number v-model:value="customYuan" :min="1" :max="10000" placeholder="输入金额" @update:value="v => amount = v * 100" />
      </n-form-item>

      <n-divider />

      <n-form-item label="支付方式">
        <n-radio-group v-model:value="paymentMethod">
          <n-space>
            <n-radio v-for="m in methods" :key="m.id" :value="m.id">
              {{ m.name }}
              <n-tag v-if="m.handling_fee_percent" size="tiny" type="warning">+{{ m.handling_fee_percent }}%</n-tag>
            </n-radio>
          </n-space>
        </n-radio-group>
      </n-form-item>

      <n-button type="primary" size="large" block :loading="loading" @click="handleRecharge" :disabled="!amount || !paymentMethod">
        充值 ¥{{ (amount / 100).toFixed(2) }}
      </n-button>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useMessage } from 'naive-ui'
import { createOrder, checkoutOrder, getPaymentMethods } from '@/api'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const message = useMessage()
const loading = ref(false)
const amount = ref(1000) // 10元 in cents
const customYuan = ref(null)
const paymentMethod = ref(null)
const methods = ref([])

const presetAmounts = [1000, 5000, 10000, 50000, 100000] // 10, 50, 100, 500, 1000 yuan in cents

onMounted(async () => {
  try {
    const res = await getPaymentMethods()
    methods.value = res.data || []
    if (methods.value.length) paymentMethod.value = methods.value[0].id
  } catch (e) {}
})

async function handleRecharge() {
  if (!amount.value || amount.value < 100) {
    message.warning('最低充值1元')
    return
  }
  loading.value = true
  try {
    // 1. 创建充值订单
    const orderRes = await createOrder({ type: 'recharge', amount: amount.value })
    const tradeNo = orderRes.data

    // 2. 发起支付
    const checkoutRes = await checkoutOrder({ trade_no: tradeNo, method: paymentMethod.value })
    const { type, data } = checkoutRes

    if (type === -1) {
      message.success('充值成功')
      userStore.fetchInfo()
    } else if (type === 0) {
      // redirect
      window.location.href = data
    } else if (type === 1) {
      // qrcode or form
      window.location.href = data
    }
  } catch (e) {
    message.error(e.message || '充值失败')
  } finally {
    loading.value = false
  }
}
</script>
