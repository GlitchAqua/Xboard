<template>
  <n-layout has-sider style="height: 100vh">
    <n-layout-sider
      bordered
      :collapsed="collapsed"
      collapse-mode="width"
      :collapsed-width="64"
      :width="220"
      show-trigger
      @collapse="collapsed = true"
      @expand="collapsed = false"
      :native-scrollbar="false"
      style="background: #fff"
    >
      <div style="padding: 16px; text-align: center; font-weight: bold; font-size: 18px">
        {{ collapsed ? '⚡' : appTitle }}
      </div>
      <n-menu
        :collapsed="collapsed"
        :options="menuOptions"
        :value="currentKey"
        @update:value="handleMenuClick"
      />
    </n-layout-sider>
    <n-layout>
      <n-layout-header bordered style="height: 56px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; background: #fff">
        <span style="font-size: 16px; font-weight: 500">{{ pageTitle }}</span>
        <n-space align="center">
          <n-tag v-if="userStore.isUsageMode" type="info" size="small">
            余额: ¥{{ userStore.balanceYuan }}
          </n-tag>
          <n-dropdown :options="userMenuOptions" @select="handleUserMenu">
            <n-button quaternary>
              {{ userStore.info?.email || '用户' }}
            </n-button>
          </n-dropdown>
        </n-space>
      </n-layout-header>
      <n-layout-content style="padding: 24px" :native-scrollbar="false">
        <router-view />
      </n-layout-content>
    </n-layout>
  </n-layout>
</template>

<script setup>
import { ref, computed, h, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NIcon } from 'naive-ui'
import {
  HomeOutline, WalletOutline, ServerOutline, StatsChartOutline,
  ChatbubblesOutline, BookOutline, MegaphoneOutline, PeopleOutline,
  PersonOutline, CartOutline, CubeOutline, ListOutline, BagHandleOutline
} from '@vicons/ionicons5'
import { useUserStore } from '@/stores/user'

const collapsed = ref(false)
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const appTitle = window.settings?.title || 'Xboard'

onMounted(async () => {
  await userStore.fetchInfo()
  userStore.fetchStat()
})

const icon = (i) => () => h(NIcon, null, { default: () => h(i) })

const currentKey = computed(() => route.name)

const pageTitle = computed(() => {
  const titles = {
    Dashboard: '仪表盘',
    Recharge: '充值额度',
    Plan: '购买订阅',
    TrafficPackage: '流量包',
    BalanceLogs: '余额明细',
    OrderList: '我的订单',
    OrderDetail: '订单详情',
    ServerList: '我的节点',
    TrafficStats: '流量统计',
    TicketList: '工单',
    TicketDetail: '工单详情',
    Knowledge: '使用文档',
    Notices: '公告',
    Invite: '邀请返利',
    Profile: '个人设置'
  }
  return titles[route.name] || ''
})

const menuOptions = computed(() => {
  const isUsage = userStore.isUsageMode
  const common = [
    { label: '仪表盘', key: 'Dashboard', icon: icon(HomeOutline) }
  ]

  const billingMenu = isUsage
    ? [
        { label: '充值额度', key: 'Recharge', icon: icon(WalletOutline) },
        { label: '流量包', key: 'TrafficPackage', icon: icon(CubeOutline) },
        { label: '余额明细', key: 'BalanceLogs', icon: icon(ListOutline) },
      ]
    : [
        { label: '购买订阅', key: 'Plan', icon: icon(CartOutline) },
      ]

  const rest = [
    { label: '我的订单', key: 'OrderList', icon: icon(BagHandleOutline) },
    { label: '我的节点', key: 'ServerList', icon: icon(ServerOutline) },
    { label: '流量统计', key: 'TrafficStats', icon: icon(StatsChartOutline) },
    { label: '工单', key: 'TicketList', icon: icon(ChatbubblesOutline) },
    { label: '使用文档', key: 'Knowledge', icon: icon(BookOutline) },
    { label: '公告', key: 'Notices', icon: icon(MegaphoneOutline) },
    { label: '邀请返利', key: 'Invite', icon: icon(PeopleOutline) },
    { label: '个人设置', key: 'Profile', icon: icon(PersonOutline) },
  ]

  return [...common, ...billingMenu, ...rest]
})

const userMenuOptions = [
  { label: '个人设置', key: 'profile' },
  { label: '退出登录', key: 'logout' }
]

function handleMenuClick(key) {
  router.push({ name: key })
}

function handleUserMenu(key) {
  if (key === 'logout') {
    userStore.logout()
    router.push('/login')
  } else if (key === 'profile') {
    router.push({ name: 'Profile' })
  }
}
</script>
