<template>
  <n-space vertical :size="24">
    <n-card title="节点列表">
      <template #header-extra>
        <n-button @click="fetchServers" :loading="loading" size="small" quaternary>刷新</n-button>
      </template>

      <n-spin :show="loading">
        <n-collapse v-if="groupedServers.length" default-expanded-names="all" :accordion="false">
          <n-collapse-item
            v-for="group in groupedServers"
            :key="group.name"
            :title="group.name + ' (' + group.servers.length + ')'"
            :name="group.name"
          >
            <n-data-table
              :columns="columns"
              :data="group.servers"
              :row-key="row => row.id"
              :bordered="false"
              size="small"
            />
          </n-collapse-item>
        </n-collapse>
        <n-empty v-if="!loading && !groupedServers.length" description="暂无可用节点" />
      </n-spin>
    </n-card>
  </n-space>
</template>

<script setup>
import { ref, computed, h, onMounted } from 'vue'
import { NTag, NSpace } from 'naive-ui'
import { getServers } from '@/api'

const loading = ref(false)
const servers = ref([])

const columns = [
  { title: '名称', key: 'name', ellipsis: { tooltip: true } },
  {
    title: '标签',
    key: 'tags',
    render: row => {
      const tags = row.tags || []
      if (!tags.length) return '-'
      return h(NSpace, { size: 4 }, () =>
        tags.map(t => h(NTag, { size: 'tiny', bordered: false }, () => t))
      )
    }
  },
  {
    title: '倍率',
    key: 'rate',
    width: 80,
    render: row => {
      const rate = row.rate ?? 1
      const type = rate > 1 ? 'warning' : rate < 1 ? 'success' : 'default'
      return h(NTag, { type, size: 'small' }, () => rate + 'x')
    }
  },
  {
    title: '状态',
    key: 'online',
    width: 80,
    render: row => {
      if (row.available_status === false || row.is_available === false) {
        return h(NTag, { type: 'error', size: 'small' }, () => '维护')
      }
      return h(NTag, { type: 'success', size: 'small' }, () => '在线')
    }
  }
]

const groupedServers = computed(() => {
  const groups = {}
  for (const s of servers.value) {
    const key = s.group_id || s.type || '默认'
    const name = s.group || s.type_name || key
    if (!groups[name]) groups[name] = { name, servers: [] }
    groups[name].servers.push(s)
  }
  return Object.values(groups)
})

async function fetchServers() {
  loading.value = true
  try {
    const res = await getServers()
    servers.value = res.data || []
  } catch (e) {
    servers.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchServers())
</script>
