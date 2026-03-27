<template>
  <n-space vertical :size="24">
    <n-card title="流量使用统计">
      <template #header-extra>
        <n-button @click="fetchLog" :loading="loading" size="small" quaternary>刷新</n-button>
      </template>

      <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive style="margin-bottom: 20px">
        <n-gi span="3 m:1">
          <n-statistic label="今日上传">{{ formatBytes(todayUpload) }}</n-statistic>
        </n-gi>
        <n-gi span="3 m:1">
          <n-statistic label="今日下载">{{ formatBytes(todayDownload) }}</n-statistic>
        </n-gi>
        <n-gi span="3 m:1">
          <n-statistic label="今日合计">{{ formatBytes(todayUpload + todayDownload) }}</n-statistic>
        </n-gi>
      </n-grid>

      <n-data-table
        :columns="columns"
        :data="trafficLog"
        :loading="loading"
        :row-key="row => row.record_at"
        size="small"
        :pagination="{ pageSize: 31 }"
      />
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMessage } from 'naive-ui'
import { getTrafficLog } from '@/api'

const message = useMessage()
const loading = ref(false)
const trafficLog = ref([])

const todayUpload = computed(() => {
  if (!trafficLog.value.length) return 0
  return trafficLog.value[0]?.u || 0
})

const todayDownload = computed(() => {
  if (!trafficLog.value.length) return 0
  return trafficLog.value[0]?.d || 0
})

const columns = [
  {
    title: '日期',
    key: 'record_at',
    render: row => row.record_at ? new Date(row.record_at * 1000).toLocaleDateString() : '-'
  },
  {
    title: '上传',
    key: 'u',
    render: row => formatBytes(row.u || 0)
  },
  {
    title: '下载',
    key: 'd',
    render: row => formatBytes(row.d || 0)
  },
  {
    title: '合计',
    key: 'total',
    render: row => formatBytes((row.u || 0) + (row.d || 0))
  }
]

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const gb = bytes / (1024 * 1024 * 1024)
  if (gb >= 1) return gb.toFixed(2) + ' GB'
  const mb = bytes / (1024 * 1024)
  if (mb >= 1) return mb.toFixed(2) + ' MB'
  const kb = bytes / 1024
  return kb.toFixed(2) + ' KB'
}

async function fetchLog() {
  loading.value = true
  try {
    const res = await getTrafficLog()
    trafficLog.value = res.data || []
  } catch (e) {
    message.error('获取流量日志失败')
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchLog())
</script>
