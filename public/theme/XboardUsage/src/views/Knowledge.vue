<template>
  <n-space vertical :size="24">
    <n-card title="知识库">
      <template #header-extra>
        <n-input v-model:value="searchText" placeholder="搜索文章..." clearable style="width: 240px" />
      </template>

      <n-spin :show="loading">
        <n-tabs v-model:value="activeCategory" type="line">
          <n-tab-pane name="all" tab="全部" />
          <n-tab-pane v-for="cat in categories" :key="cat.id" :name="String(cat.id)" :tab="cat.name" />
        </n-tabs>

        <n-list hoverable clickable style="margin-top: 12px">
          <n-list-item v-for="a in filteredArticles" :key="a.id" @click="openArticle(a)" style="cursor: pointer">
            <n-thing :title="a.title">
              <template #header-extra>
                <n-tag v-if="a.category?.name" size="small" bordered>{{ a.category.name }}</n-tag>
              </template>
              <template #description>
                {{ a.updated_at ? new Date(a.updated_at * 1000).toLocaleDateString() : '' }}
              </template>
            </n-thing>
          </n-list-item>
        </n-list>
        <n-empty v-if="!loading && !filteredArticles.length" description="暂无文章" style="margin-top: 24px" />
      </n-spin>
    </n-card>

    <n-modal v-model:show="showArticle" preset="card" :title="currentArticle.title" style="width: 700px; max-width: 90vw">
      <div v-html="currentArticle.body" style="line-height: 1.8"></div>
      <template #footer>
        <n-text depth="3">
          更新于: {{ currentArticle.updated_at ? new Date(currentArticle.updated_at * 1000).toLocaleString() : '-' }}
        </n-text>
      </template>
    </n-modal>
  </n-space>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getKnowledge, getKnowledgeCategory } from '@/api'

const loading = ref(false)
const articles = ref([])
const categories = ref([])
const activeCategory = ref('all')
const searchText = ref('')
const showArticle = ref(false)
const currentArticle = ref({})

const filteredArticles = computed(() => {
  let list = articles.value
  if (activeCategory.value !== 'all') {
    list = list.filter(a => String(a.category_id) === activeCategory.value)
  }
  if (searchText.value.trim()) {
    const kw = searchText.value.toLowerCase()
    list = list.filter(a =>
      (a.title || '').toLowerCase().includes(kw) ||
      (a.body || '').toLowerCase().includes(kw)
    )
  }
  return list
})

function openArticle(article) {
  currentArticle.value = article
  showArticle.value = true
}

onMounted(async () => {
  loading.value = true
  try {
    const [artRes, catRes] = await Promise.all([
      getKnowledge(),
      getKnowledgeCategory()
    ])
    articles.value = artRes.data?.data || artRes.data || []
    categories.value = catRes.data || []
  } catch (e) {}
  loading.value = false
})
</script>
