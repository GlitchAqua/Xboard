<template>
  <n-config-provider :theme-overrides="themeOverrides">
    <n-message-provider>
      <n-dialog-provider>
        <router-view />
      </n-dialog-provider>
    </n-message-provider>
  </n-config-provider>
</template>

<script setup>
import { computed } from 'vue'

const themeOverrides = computed(() => {
  const color = window.settings?.theme?.color || 'default'
  const colors = {
    default: '#2080f0',
    green: '#18a058',
    purple: '#8b5cf6'
  }
  return {
    common: {
      primaryColor: colors[color] || colors.default
    }
  }
})
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
</style>
