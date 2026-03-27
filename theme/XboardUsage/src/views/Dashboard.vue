<template>
  <n-space vertical :size="24">
    <!-- 按量模式: 余额 + 预估流量 -->
    <n-grid v-if="userStore.isUsageMode" :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="钱包余额">
            <template #prefix>¥</template>
            {{ userStore.balanceYuan }}
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="预估可用流量">
            {{ billingStatus?.estimated_traffic_gb || 0 }} GB
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="流量包剩余">
            {{ billingStatus?.package_remaining_gb || 0 }} GB
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="单价">
            ¥{{ ((billingStatus?.price_per_gb || 0) / 100).toFixed(2) }}/GB
          </n-statistic>
        </n-card>
      </n-gi>
    </n-grid>

    <!-- 订阅模式: 套餐信息 -->
    <n-grid v-else :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="当前套餐">
            {{ subscribe?.plan?.name || '未订阅' }}
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="到期时间">
            {{ subscribe?.expired_at ? new Date(subscribe.expired_at * 1000).toLocaleDateString() : '无' }}
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="已用流量">
            {{ formatBytes(subscribe?.u + subscribe?.d) }}
          </n-statistic>
        </n-card>
      </n-gi>
      <n-gi span="4 m:1">
        <n-card>
          <n-statistic label="总流量">
            {{ formatBytes(subscribe?.transfer_enable) }}
          </n-statistic>
        </n-card>
      </n-gi>
    </n-grid>

    <!-- 快捷操作 -->
    <n-card title="快捷操作">
      <n-space>
        <n-button v-if="userStore.isUsageMode" type="primary" @click="$router.push('/recharge')">充值</n-button>
        <n-button v-if="userStore.isUsageMode" @click="$router.push('/traffic-package')">购买流量包</n-button>
        <n-button v-if="!userStore.isUsageMode" type="primary" @click="$router.push('/plan')">购买订阅</n-button>
        <n-button @click="$router.push('/server')">查看节点</n-button>
        <n-button @click="$router.push('/ticket')">提交工单</n-button>
      </n-space>
    </n-card>

    <!-- 公告 -->
    <n-card title="最新公告" v-if="notices.length">
      <n-list>
        <n-list-item v-for="n in notices" :key="n.id">
          <n-thing :title="n.title" :description="new Date(n.created_at * 1000).toLocaleDateString()" />
        </n-list-item>
      </n-list>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { getNotices } from '@/api'

const userStore = useUserStore()
const notices = ref([])

const billingStatus = computed(() => userStore.billingStatus)
const subscribe = computed(() => userStore.subscribe)

onMounted(async () => {
  await userStore.fetchInfo()
  userStore.fetchSubscribe()
  try {
    const res = await getNotices({ page: 1 })
    notices.value = (res.data?.data || res.data || []).slice(0, 3)
  } catch (e) {}
})

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const gb = bytes / (1024 * 1024 * 1024)
  if (gb >= 1) return gb.toFixed(2) + ' GB'
  const mb = bytes / (1024 * 1024)
  return mb.toFixed(2) + ' MB'
}
</script>
