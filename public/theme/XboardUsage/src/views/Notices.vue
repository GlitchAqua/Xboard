<template>
  <n-space vertical :size="24">
    <n-card title="系统公告">
      <n-spin :show="loading">
        <n-list bordered>
          <n-list-item v-for="notice in notices" :key="notice.id" @click="openNotice(notice)" style="cursor: pointer">
            <n-thing :title="notice.title">
              <template #description>
                {{ notice.created_at ? new Date(notice.created_at * 1000).toLocaleString() : '' }}
              </template>
            </n-thing>
          </n-list-item>
        </n-list>
        <n-empty v-if="!loading && !notices.length" description="暂无公告" />

        <n-space justify="center" style="margin-top: 16px" v-if="hasMore">
          <n-button @click="loadMore" :loading="loadingMore" size="small">加载更多</n-button>
        </n-space>
      </n-spin>
    </n-card>

    <n-modal v-model:show="showDetail" preset="card" :title="current.title" style="width: 700px; max-width: 90vw">
      <div v-html="current.content" style="line-height: 1.8"></div>
      <template #footer>
        <n-text depth="3">
          发布于: {{ current.created_at ? new Date(current.created_at * 1000).toLocaleString() : '-' }}
        </n-text>
      </template>
    </n-modal>
  </n-space>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getNotices } from '@/api'

const loading = ref(false)
const loadingMore = ref(false)
const notices = ref([])
const page = ref(1)
const hasMore = ref(true)
const showDetail = ref(false)
const current = ref({})

function openNotice(notice) {
  current.value = notice
  showDetail.value = true
}

async function fetchNotices(pageNum) {
  try {
    const res = await getNotices({ page: pageNum })
    const data = res.data
    const items = data?.data || data || []
    if (pageNum === 1) {
      notices.value = items
    } else {
      notices.value.push(...items)
    }
    hasMore.value = items.length >= 15
  } catch (e) {
    hasMore.value = false
  }
}

async function loadMore() {
  loadingMore.value = true
  page.value++
  await fetchNotices(page.value)
  loadingMore.value = false
}

onMounted(async () => {
  loading.value = true
  await fetchNotices(1)
  loading.value = false
})
</script>
