<template>
  <n-space vertical :size="24">
    <!-- 我的流量包 -->
    <n-card title="我的流量包">
      <n-empty v-if="!activePackages.length" description="暂无流量包" />
      <n-grid v-else :cols="3" :x-gap="12" :y-gap="12" responsive="screen" item-responsive>
        <n-gi v-for="(p, idx) in activePackages" :key="p.id" span="3 m:1">
          <n-card size="small" :style="{ borderLeft: statusBorder(p) }">
            <n-thing>
              <template #header>
                <n-space align="center" :size="8">
                  <span>{{ p.traffic_package?.name || '流量包' }}</span>
                  <n-tag v-if="p.traffic_package?.type === 'permanent'" size="tiny" type="success">永久</n-tag>
                  <n-tag v-else size="tiny" type="info">限时</n-tag>
                  <n-tag v-if="p.status === 3" size="tiny" type="warning">等待续费</n-tag>
                </n-space>
              </template>
              <template #header-extra>
                <n-tag size="tiny" :type="p.status === 1 ? 'success' : 'warning'">
                  #{{ idx + 1 }} 优先
                </n-tag>
              </template>
              <template #description>
                <n-space vertical :size="8">
                  <n-progress
                    type="line"
                    :percentage="Math.min(100, Number((p.used_bytes / p.traffic_bytes * 100).toFixed(1)))"
                    :status="p.used_bytes >= p.traffic_bytes ? 'error' : 'success'"
                  />
                  <span>{{ formatBytes(p.used_bytes) }} / {{ formatBytes(p.traffic_bytes) }}</span>
                  <span v-if="p.expired_at">到期: {{ new Date(p.expired_at * 1000).toLocaleDateString() }}</span>
                  <span v-else>永不过期</span>
                  <span v-if="p.status === 3" style="color: #f0a020">
                    续费宽限期剩余 {{ p.renew_grace_days_left ?? '?' }} 天
                  </span>
                  <span v-if="p.renewal_count > 0" style="color: #888; font-size: 12px">
                    已续费 {{ p.renewal_count }} 次
                  </span>
                </n-space>
              </template>
            </n-thing>
            <n-divider style="margin: 8px 0" />
            <n-space justify="space-between" align="center">
              <n-space align="center" :size="4">
                <span style="font-size: 12px; color: #666">自动续费</span>
                <n-switch
                  size="small"
                  :value="p.auto_renew"
                  @update:value="v => handleToggleRenew(p.id, v)"
                />
              </n-space>
              <n-space :size="4">
                <n-button size="tiny" :disabled="idx === 0" @click="movePriority(idx, -1)">↑</n-button>
                <n-button size="tiny" :disabled="idx === activePackages.length - 1" @click="movePriority(idx, 1)">↓</n-button>
              </n-space>
            </n-space>
          </n-card>
        </n-gi>
      </n-grid>
    </n-card>

    <!-- 购买流量包 -->
    <n-card title="购买流量包">
      <n-empty v-if="!packages.length" description="暂无可购买的流量包" />
      <n-grid v-else :cols="3" :x-gap="12" :y-gap="12" responsive="screen" item-responsive>
        <n-gi v-for="p in packages" :key="p.id" span="3 m:1">
          <n-card size="small" hoverable>
            <n-thing :description="p.description">
              <template #header>
                <n-space align="center" :size="8">
                  {{ p.name }}
                  <n-tag v-if="p.type === 'permanent'" size="tiny" type="success">永久</n-tag>
                  <n-tag v-else size="tiny" type="info">限时{{ p.validity_days }}天</n-tag>
                </n-space>
              </template>
              <template #header-extra>
                <n-tag type="warning">¥{{ (p.price / 100).toFixed(2) }}</n-tag>
              </template>
            </n-thing>
            <n-space style="margin-top: 12px" justify="space-between" align="center">
              <span>{{ formatBytes(p.traffic_bytes) }}</span>
              <n-space align="center" :size="8">
                <n-checkbox v-model:checked="purchaseAutoRenew" size="small">自动续费</n-checkbox>
                <n-button type="primary" size="small" :loading="buying === p.id" @click="handlePurchase(p)">
                  购买
                </n-button>
              </n-space>
            </n-space>
          </n-card>
        </n-gi>
      </n-grid>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useMessage } from 'naive-ui'
import {
  getTrafficPackages, purchaseTrafficPackage, getActiveTrafficPackages,
  togglePackageAutoRenew, updatePackagePriority
} from '@/api'
import { useUserStore } from '@/stores/user'

const message = useMessage()
const userStore = useUserStore()
const packages = ref([])
const activePackages = ref([])
const buying = ref(null)
const purchaseAutoRenew = ref(false)

onMounted(loadData)

async function loadData() {
  try {
    const [pkgRes, activeRes] = await Promise.all([
      getTrafficPackages(),
      getActiveTrafficPackages()
    ])
    packages.value = pkgRes.data || []
    const list = activeRes.data || []
    // 计算续费宽限剩余天数
    list.forEach(p => {
      if (p.status === 3 && p.renew_failed_at) {
        const left = Math.ceil((p.renew_failed_at + 15 * 86400 - Date.now() / 1000) / 86400)
        p.renew_grace_days_left = Math.max(0, left)
      }
    })
    activePackages.value = list
  } catch (e) {}
}

async function handlePurchase(pkg) {
  buying.value = pkg.id
  try {
    await purchaseTrafficPackage({
      package_id: pkg.id,
      auto_renew: purchaseAutoRenew.value
    })
    message.success('购买成功')
    userStore.fetchInfo()
    loadData()
  } catch (e) {
    message.error(e.message || '购买失败')
  } finally {
    buying.value = null
  }
}

async function handleToggleRenew(id, value) {
  try {
    await togglePackageAutoRenew({ id, auto_renew: value })
    const pkg = activePackages.value.find(p => p.id === id)
    if (pkg) pkg.auto_renew = value
    message.success(value ? '已开启自动续费' : '已关闭自动续费')
  } catch (e) {
    message.error(e.message || '操作失败')
  }
}

async function movePriority(idx, direction) {
  const list = [...activePackages.value]
  const target = idx + direction
  if (target < 0 || target >= list.length) return
  ;[list[idx], list[target]] = [list[target], list[idx]]

  const priorities = list.map((p, i) => ({ id: p.id, priority: i }))
  try {
    await updatePackagePriority({ priorities })
    activePackages.value = list
    message.success('优先级已更新')
  } catch (e) {
    message.error(e.message || '更新失败')
  }
}

function statusBorder(p) {
  if (p.status === 3) return '3px solid #f0a020'
  if (p.status === 1) return '3px solid #18a058'
  return '3px solid #d9d9d9'
}

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const gb = bytes / (1024 * 1024 * 1024)
  if (gb >= 1) return gb.toFixed(2) + ' GB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}
</script>
