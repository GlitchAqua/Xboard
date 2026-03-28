<template>
  <n-space vertical :size="24">
    <n-card title="选择订阅套餐">
      <n-spin :show="loading">
        <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
          <n-gi span="3 m:1" v-for="plan in plans" :key="plan.id">
            <n-card
              :class="{ 'selected-plan': selectedPlan?.id === plan.id }"
              hoverable
              @click="selectedPlan = plan"
              style="cursor: pointer"
            >
              <template #header>
                <n-space justify="space-between" align="center">
                  <span>{{ plan.name }}</span>
                  <n-tag v-if="selectedPlan?.id === plan.id" type="primary" size="small">已选</n-tag>
                </n-space>
              </template>
              <n-space vertical :size="8">
                <div v-html="plan.content" style="font-size: 13px; color: #666"></div>
                <n-divider style="margin: 8px 0" />
                <n-space vertical :size="4">
                  <div v-if="plan.month_price !== null">
                    月付: <n-text strong>¥{{ (plan.month_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.quarter_price !== null">
                    季付: <n-text strong>¥{{ (plan.quarter_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.half_year_price !== null">
                    半年付: <n-text strong>¥{{ (plan.half_year_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.year_price !== null">
                    年付: <n-text strong>¥{{ (plan.year_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.two_year_price !== null">
                    两年付: <n-text strong>¥{{ (plan.two_year_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.three_year_price !== null">
                    三年付: <n-text strong>¥{{ (plan.three_year_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.onetime_price !== null">
                    一次性: <n-text strong>¥{{ (plan.onetime_price / 100).toFixed(2) }}</n-text>
                  </div>
                  <div v-if="plan.reset_price !== null">
                    重置流量: <n-text strong>¥{{ (plan.reset_price / 100).toFixed(2) }}</n-text>
                  </div>
                </n-space>
              </n-space>
            </n-card>
          </n-gi>
        </n-grid>
        <n-empty v-if="!loading && !plans.length" description="暂无可用套餐" />
      </n-spin>
    </n-card>

    <n-card title="订单选项" v-if="selectedPlan">
      <n-form label-placement="left" label-width="80">
        <n-form-item label="付费周期">
          <n-radio-group v-model:value="period">
            <n-space>
              <n-radio v-for="p in availablePeriods" :key="p.value" :value="p.value">
                {{ p.label }} - ¥{{ (p.price / 100).toFixed(2) }}
              </n-radio>
            </n-space>
          </n-radio-group>
        </n-form-item>

        <n-form-item label="优惠码">
          <n-input-group>
            <n-input v-model:value="couponCode" placeholder="输入优惠码" clearable />
            <n-button :loading="couponLoading" @click="handleCheckCoupon" :disabled="!couponCode">验证</n-button>
          </n-input-group>
        </n-form-item>

        <n-alert v-if="couponInfo" type="success" style="margin-bottom: 16px">
          优惠码有效! 优惠: {{ couponInfo.type === 1 ? '¥' + (couponInfo.value / 100).toFixed(2) : couponInfo.value + '%' }}
        </n-alert>

        <n-divider />

        <n-space justify="end">
          <n-button type="primary" size="large" :loading="orderLoading" @click="handleCreateOrder">
            提交订单
          </n-button>
        </n-space>
      </n-form>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage } from 'naive-ui'
import { getPlans, createOrder, checkCoupon } from '@/api'

const router = useRouter()
const message = useMessage()

const loading = ref(false)
const plans = ref([])
const selectedPlan = ref(null)
const period = ref(null)
const couponCode = ref('')
const couponInfo = ref(null)
const couponLoading = ref(false)
const orderLoading = ref(false)

const periodMap = [
  { value: 'month_price', label: '月付' },
  { value: 'quarter_price', label: '季付' },
  { value: 'half_year_price', label: '半年付' },
  { value: 'year_price', label: '年付' },
  { value: 'two_year_price', label: '两年付' },
  { value: 'three_year_price', label: '三年付' },
  { value: 'onetime_price', label: '一次性' },
  { value: 'reset_price', label: '重置流量' }
]

const availablePeriods = computed(() => {
  if (!selectedPlan.value) return []
  return periodMap
    .filter(p => selectedPlan.value[p.value] !== null && selectedPlan.value[p.value] !== undefined)
    .map(p => ({ ...p, price: selectedPlan.value[p.value] }))
})

async function handleCheckCoupon() {
  if (!couponCode.value || !selectedPlan.value) return
  couponLoading.value = true
  try {
    const res = await checkCoupon({ code: couponCode.value, plan_id: selectedPlan.value.id })
    couponInfo.value = res.data
    message.success('优惠码验证成功')
  } catch (e) {
    couponInfo.value = null
    message.error(e.response?.data?.message || '优惠码无效')
  } finally {
    couponLoading.value = false
  }
}

async function handleCreateOrder() {
  if (!selectedPlan.value || !period.value) {
    message.warning('请选择套餐和付费周期')
    return
  }
  orderLoading.value = true
  try {
    const data = {
      plan_id: selectedPlan.value.id,
      period: period.value
    }
    if (couponCode.value) data.coupon_code = couponCode.value
    const res = await createOrder(data)
    const tradeNo = res.data?.trade_no || res.data
    message.success('订单创建成功')
    router.push(`/order/${tradeNo}`)
  } catch (e) {
    message.error(e.response?.data?.message || '创建订单失败')
  } finally {
    orderLoading.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await getPlans()
    plans.value = res.data || []
  } catch (e) {
    message.error('获取套餐列表失败')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.selected-plan {
  border-color: var(--n-color-target);
  box-shadow: 0 0 0 1px var(--primary-color, #18a058);
}
</style>
