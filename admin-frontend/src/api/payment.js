import request from '@/utils/request'
// 支付渠道管理
export const getChannels = () => request.get('/pay/channels')
export const createChannel = (data) => request.post('/pay/channels', data)
export const updateChannel = (id, data) => request.put(`/pay/channels/${id}`, data)
export const deleteChannel = (id) => request.delete(`/pay/channels/${id}`)
// 充值套餐管理
export const getPackages = () => request.get('/pay/packages')
export const createPackage = (data) => request.post('/pay/packages', data)
export const updatePackage = (id, data) => request.put(`/pay/packages/${id}`, data)
export const deletePackage = (id) => request.delete(`/pay/packages/${id}`)
// 报表统计
export const getReportSummary = () => request.get('/report/summary')
export const getReportTrend = (days = 30) => request.get('/report/trend', { params: { days } })
export const getReportChannels = () => request.get('/report/channels')
export const getReportPackages = () => request.get('/report/packages')
