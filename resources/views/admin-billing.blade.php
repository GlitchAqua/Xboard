<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>按量计费管理 - {{ $title }}</title>
  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; }
    .header { background: #001529; color: #fff; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
    .header h1 { font-size: 18px; font-weight: 500; }
    .header a { color: #8bb4e7; text-decoration: none; }
    .container { max-width: 1200px; margin: 24px auto; padding: 0 24px; }
    .card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { font-size: 16px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
    .form-row { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
    .form-item { flex: 1; min-width: 200px; }
    .form-item label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
    .form-item input, .form-item select, .form-item textarea { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; }
    .form-item input:focus, .form-item select:focus { border-color: #1890ff; outline: none; }
    .btn { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
    .btn-primary { background: #1890ff; color: #fff; }
    .btn-primary:hover { background: #40a9ff; }
    .btn-success { background: #52c41a; color: #fff; }
    .btn-danger { background: #ff4d4f; color: #fff; }
    .btn-danger:hover { background: #ff7875; }
    .btn-sm { padding: 4px 12px; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    th { background: #fafafa; font-weight: 500; color: #666; }
    .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
    .tag-green { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
    .tag-red { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
    .tag-blue { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
    .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; }
    .alert-success { background: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; }
    .alert-error { background: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal { background: #fff; border-radius: 8px; padding: 24px; width: 560px; max-width: 90vw; max-height: 80vh; overflow-y: auto; }
    .modal h3 { margin-bottom: 16px; }
    .actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
  </style>
</head>
<body>

<div id="app">
  <div class="header">
    <h1>按量计费管理</h1>
    <a href="/{{ $secure_path }}">← 返回管理后台</a>
  </div>

  <!-- 登录界面 -->
  <div v-if="!isLoggedIn" style="max-width: 400px; margin: 80px auto; padding: 0 24px">
    <div class="card">
      <h2>管理员登录</h2>
      <div v-if="loginError" class="alert alert-error">@{{ loginError }}</div>
      <div style="margin-bottom: 12px">
        <label style="display:block; font-size:13px; color:#666; margin-bottom:4px">邮箱</label>
        <input v-model="loginForm.email" placeholder="管理员邮箱" style="width:100%; padding:8px 12px; border:1px solid #d9d9d9; border-radius:4px">
      </div>
      <div style="margin-bottom: 16px">
        <label style="display:block; font-size:13px; color:#666; margin-bottom:4px">密码</label>
        <input v-model="loginForm.password" type="password" placeholder="密码" style="width:100%; padding:8px 12px; border:1px solid #d9d9d9; border-radius:4px" @keyup.enter="handleLogin">
      </div>
      <button class="btn btn-primary" style="width:100%" @click="handleLogin" :disabled="loginLoading">
        @{{ loginLoading ? '登录中...' : '登录' }}
      </button>
    </div>
  </div>

  <div v-else class="container">
    <!-- 提示消息 -->
    <div v-if="message" :class="'alert alert-' + messageType" @click="message=''">
      @{{ message }}
    </div>

    <!-- 计费设置 -->
    <div class="card">
      <h2>计费设置</h2>
      <div class="form-row">
        <div class="form-item">
          <label>计费模式</label>
          <select v-model="config.billing_mode">
            <option value="subscription">订阅模式</option>
            <option value="usage">按量计费</option>
          </select>
        </div>
        <div class="form-item">
          <label>每GB单价 (分, 如100=¥1/GB)</label>
          <input type="number" v-model.number="config.usage_price_per_gb" min="1">
        </div>
        <div class="form-item">
          <label>最低余额阈值 (分, 低于此值停服)</label>
          <input type="number" v-model.number="config.usage_min_balance" min="0">
        </div>
        <div class="form-item">
          <label>余额预警阈值 (分)</label>
          <input type="number" v-model.number="config.usage_low_balance_threshold" min="0">
        </div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" @click="saveConfig" :disabled="saving">保存设置</button>
      </div>
    </div>

    <!-- 流量包管理 -->
    <div class="card">
      <h2>
        流量包管理
        <button class="btn btn-primary btn-sm" style="float:right" @click="openAddModal">+ 添加流量包</button>
      </h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>名称</th>
            <th>类型</th>
            <th>流量</th>
            <th>价格</th>
            <th>有效期</th>
            <th>显示</th>
            <th>销售</th>
            <th>排序</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!packages.length">
            <td colspan="10" style="text-align:center; color:#999">暂无流量包，点击右上角添加</td>
          </tr>
          <tr v-for="p in packages" :key="p.id">
            <td>@{{ p.id }}</td>
            <td>@{{ p.name }}</td>
            <td><span :class="p.type === 'permanent' ? 'tag tag-blue' : 'tag tag-green'">@{{ p.type === 'permanent' ? '永久' : '限时' }}</span></td>
            <td>@{{ formatBytes(p.traffic_bytes) }}</td>
            <td>¥@{{ (p.price / 100).toFixed(2) }}</td>
            <td>@{{ p.type === 'permanent' ? '—' : p.validity_days + '天' }}</td>
            <td><span :class="p.show ? 'tag tag-green' : 'tag tag-red'">@{{ p.show ? '是' : '否' }}</span></td>
            <td><span :class="p.sell ? 'tag tag-green' : 'tag tag-red'">@{{ p.sell ? '是' : '否' }}</span></td>
            <td>@{{ p.sort }}</td>
            <td>
              <button class="btn btn-primary btn-sm" @click="openEditModal(p)">编辑</button>
              <button class="btn btn-danger btn-sm" @click="deletePackage(p.id)">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 添加/编辑弹窗 -->
  <div class="modal-overlay" v-if="showModal" @click.self="showModal=false">
    <div class="modal">
      <h3>@{{ editingId ? '编辑流量包' : '添加流量包' }}</h3>
      <div class="form-row">
        <div class="form-item">
          <label>名称 *</label>
          <input v-model="form.name" placeholder="如: 10GB流量包">
        </div>
        <div class="form-item">
          <label>类型 *</label>
          <select v-model="form.type">
            <option value="time-limited">限时包 (有到期时间)</option>
            <option value="permanent">永久包 (用完即止)</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-item">
          <label>流量 (GB) *</label>
          <input type="number" v-model.number="form.traffic_gb" min="0.1" step="0.1" placeholder="10">
        </div>
        <div class="form-item">
          <label>价格 (元) *</label>
          <input type="number" v-model.number="form.price_yuan" min="0.01" step="0.01" placeholder="5.00">
        </div>
      </div>
      <div class="form-row">
        <div class="form-item" v-if="form.type === 'time-limited'">
          <label>有效天数 *</label>
          <input type="number" v-model.number="form.validity_days" min="1" placeholder="30">
        </div>
        <div class="form-item">
          <label>排序 (越小越前)</label>
          <input type="number" v-model.number="form.sort" placeholder="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-item">
          <label>速度限制 (Mbps, 留空不限)</label>
          <input type="number" v-model.number="form.speed_limit" placeholder="不限">
        </div>
        <div class="form-item">
          <label>服务器组ID (留空不限)</label>
          <input type="number" v-model.number="form.group_id" placeholder="不限">
        </div>
      </div>
      <div class="form-row">
        <div class="form-item" style="min-width:100%">
          <label>描述</label>
          <textarea v-model="form.description" rows="2" placeholder="流量包描述(可选)"></textarea>
        </div>
      </div>
      <div class="form-row">
        <div class="form-item">
          <label><input type="checkbox" v-model="form.show"> 前台显示</label>
        </div>
        <div class="form-item">
          <label><input type="checkbox" v-model="form.sell"> 允许购买</label>
        </div>
      </div>
      <div class="actions">
        <button class="btn" @click="showModal=false">取消</button>
        <button class="btn btn-primary" @click="submitForm" :disabled="submitting">
          @{{ editingId ? '保存修改' : '创建' }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const { createApp, ref, reactive, onMounted } = Vue

const API_BASE = '/api/v2/{{ $secure_path }}'
let authToken = localStorage.getItem('billing_admin_token') || ''

async function api(method, path, data) {
  const opts = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': authToken
    }
  }
  if (data && method !== 'GET') opts.body = JSON.stringify(data)
  const res = await fetch(API_BASE + path, opts)
  const json = await res.json()
  if (res.status === 403 || json.message === 'Unauthorized') {
    localStorage.removeItem('billing_admin_token')
    authToken = ''
    location.reload()
  }
  return json
}

async function adminLogin(email, password) {
  const res = await fetch('/api/v1/passport/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  })
  const json = await res.json()
  if (json.data?.auth_data) {
    authToken = json.data.auth_data
    localStorage.setItem('billing_admin_token', authToken)
    return true
  }
  return false
}

createApp({
  setup() {
    const isLoggedIn = ref(!!authToken)
    const loginForm = reactive({ email: '', password: '' })
    const loginLoading = ref(false)
    const loginError = ref('')

    async function handleLogin() {
      loginLoading.value = true
      loginError.value = ''
      try {
        const ok = await adminLogin(loginForm.email, loginForm.password)
        if (ok) {
          isLoggedIn.value = true
          loadPackages()
        } else {
          loginError.value = '登录失败，请检查账号密码'
        }
      } catch (e) {
        loginError.value = e.message || '网络错误'
      }
      loginLoading.value = false
    }

    const packages = ref([])
    const showModal = ref(false)
    const editingId = ref(null)
    const saving = ref(false)
    const submitting = ref(false)
    const message = ref('')
    const messageType = ref('success')

    const config = reactive({
      billing_mode: '{{ admin_setting("billing_mode", "subscription") }}',
      usage_price_per_gb: {{ (int)admin_setting("usage_price_per_gb", 100) }},
      usage_min_balance: {{ (int)admin_setting("usage_min_balance", 0) }},
      usage_low_balance_threshold: {{ (int)admin_setting("usage_low_balance_threshold", 500) }}
    })

    const form = reactive({
      name: '', description: '', traffic_gb: 10, price_yuan: 5,
      validity_days: 30, group_id: null, speed_limit: null,
      show: true, sell: true, sort: 0
    })

    function showMsg(msg, type = 'success') {
      message.value = msg
      messageType.value = type
      setTimeout(() => message.value = '', 3000)
    }

    async function loadPackages() {
      const res = await api('GET', '/traffic-package/fetch')
      if (res.data) packages.value = res.data
    }

    async function saveConfig() {
      saving.value = true
      try {
        await api('POST', '/config/save', config)
        showMsg('设置已保存，切换计费模式需刷新前台生效')
      } catch (e) {
        showMsg('保存失败: ' + e.message, 'error')
      }
      saving.value = false
    }

    function resetForm() {
      Object.assign(form, {
        name: '', description: '', traffic_gb: 10, price_yuan: 5,
        validity_days: 30, group_id: null, speed_limit: null,
        show: true, sell: true, sort: 0, type: 'time-limited'
      })
    }

    function openAddModal() {
      editingId.value = null
      resetForm()
      showModal.value = true
    }

    function openEditModal(p) {
      editingId.value = p.id
      Object.assign(form, {
        name: p.name,
        description: p.description || '',
        traffic_gb: p.traffic_bytes / (1024 * 1024 * 1024),
        price_yuan: p.price / 100,
        validity_days: p.validity_days,
        group_id: p.group_id,
        speed_limit: p.speed_limit,
        show: !!p.show,
        sell: !!p.sell,
        sort: p.sort || 0,
        type: p.type || 'time-limited'
      })
      showModal.value = true
    }

    async function submitForm() {
      if (!form.name || !form.traffic_gb || !form.price_yuan || !form.validity_days) {
        showMsg('请填写必填项', 'error')
        return
      }
      submitting.value = true
      const payload = {
        name: form.name,
        description: form.description || null,
        traffic_bytes: Math.round(form.traffic_gb * 1024 * 1024 * 1024),
        price: Math.round(form.price_yuan * 100),
        validity_days: form.type === 'permanent' ? 0 : form.validity_days,
        type: form.type,
        group_id: form.group_id || null,
        speed_limit: form.speed_limit || null,
        show: form.show ? 1 : 0,
        sell: form.sell ? 1 : 0,
        sort: form.sort || 0
      }
      try {
        if (editingId.value) {
          payload.id = editingId.value
          await api('POST', '/traffic-package/update', payload)
          showMsg('流量包已更新')
        } else {
          await api('POST', '/traffic-package/save', payload)
          showMsg('流量包已创建')
        }
        showModal.value = false
        loadPackages()
      } catch (e) {
        showMsg('操作失败: ' + e.message, 'error')
      }
      submitting.value = false
    }

    async function deletePackage(id) {
      if (!confirm('确认删除此流量包？')) return
      await api('POST', '/traffic-package/drop', { id })
      showMsg('已删除')
      loadPackages()
    }

    function formatBytes(bytes) {
      if (!bytes) return '0'
      const gb = bytes / (1024 * 1024 * 1024)
      return gb >= 1 ? gb.toFixed(2) + ' GB' : (bytes / (1024 * 1024)).toFixed(2) + ' MB'
    }

    onMounted(() => { if (isLoggedIn.value) loadPackages() })

    return {
      isLoggedIn, loginForm, loginLoading, loginError, handleLogin,
      packages, showModal, editingId, saving, submitting,
      message, messageType, config, form,
      saveConfig, openAddModal, openEditModal, submitForm,
      deletePackage, formatBytes, loadPackages, showMsg
    }
  }
}).mount('#app')
</script>
</body>
</html>
