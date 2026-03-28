import http from './http'

// Auth
export const login = data => http.post('/passport/auth/login', data)
export const register = data => http.post('/passport/auth/register', data)
export const forget = data => http.post('/passport/auth/forget', data)
export const sendEmailVerify = data => http.post('/passport/comm/sendEmailVerify', data)
export const getGuestConfig = () => http.get('/guest/comm/config')
export const getGuestPlans = () => http.get('/guest/plan/fetch')

// User
export const getUserInfo = () => http.get('/user/info')
export const getUserStat = () => http.get('/user/getStat')
export const getSubscribe = () => http.get('/user/getSubscribe')
export const getUserConfig = () => http.get('/user/comm/config')
export const changePassword = data => http.post('/user/changePassword', data)
export const updateUser = data => http.post('/user/update', data)
export const resetSecurity = () => http.get('/user/resetSecurity')
export const transfer = data => http.post('/user/transfer', data)
export const checkLogin = () => http.get('/user/checkLogin')

// Order
export const createOrder = data => http.post('/user/order/save', data)
export const checkoutOrder = data => http.post('/user/order/checkout', data)
export const checkOrder = params => http.get('/user/order/check', { params })
export const getOrderDetail = params => http.get('/user/order/detail', { params })
export const getOrders = params => http.get('/user/order/fetch', { params })
export const cancelOrder = data => http.post('/user/order/cancel', data)
export const getPaymentMethods = () => http.get('/user/order/getPaymentMethod')

// Plan
export const getPlans = () => http.get('/user/plan/fetch')

// Server
export const getServers = () => http.get('/user/server/fetch')

// Ticket
export const getTickets = params => http.get('/user/ticket/fetch', { params })
export const createTicket = data => http.post('/user/ticket/save', data)
export const replyTicket = data => http.post('/user/ticket/reply', data)
export const closeTicket = data => http.post('/user/ticket/close', data)

// Knowledge
export const getKnowledge = params => http.get('/user/knowledge/fetch', { params })
export const getKnowledgeCategory = () => http.get('/user/knowledge/getCategory')

// Notice
export const getNotices = params => http.get('/user/notice/fetch', { params })

// Invite
export const getInvite = () => http.get('/user/invite/fetch')
export const saveInvite = () => http.get('/user/invite/save')
export const getInviteDetails = params => http.get('/user/invite/details', { params })

// Stat
export const getTrafficLog = params => http.get('/user/stat/getTrafficLog', { params })

// Traffic Package (按量计费)
export const getTrafficPackages = () => http.get('/user/traffic-package/fetch')
export const purchaseTrafficPackage = data => http.post('/user/traffic-package/purchase', data)
export const getActiveTrafficPackages = () => http.get('/user/traffic-package/active')
export const togglePackageAutoRenew = data => http.post('/user/traffic-package/toggle-renew', data)
export const updatePackagePriority = data => http.post('/user/traffic-package/priority', data)

// Balance
export const getBalanceLogs = params => http.get('/user/balance/logs', { params })

// Coupon
export const checkCoupon = data => http.post('/user/coupon/check', data)

// Gift Card
export const redeemGiftCard = data => http.post('/user/gift-card/redeem', data)
export const checkGiftCard = data => http.post('/user/gift-card/check', data)

// Email binding
export const bindEmail = data => http.post('/user/bindEmail', data)
export const unbindEmail = () => http.post('/user/unbindEmail')

// Telegram
export const getTelegramBotInfo = () => http.get('/user/telegram/getBotInfo')
