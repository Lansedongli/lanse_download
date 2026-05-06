import request from './index'

export const getPackages = () => request.get('/payment/packages')
export const getChannels = () => request.get('/payment/channels')
export const createPayment = (data) => request.post('/payment/create', data)
export const queryPayment = (orderNo) => request.get(`/payment/query/${orderNo}`)

// 报表数据
export const getReportSummary = () => request.get('/report/summary')
export const getReportTrend = (days = 30) => request.get('/report/trend', { params: { days } })
export const getReportChannels = () => request.get('/report/channels')
export const getReportPackages = () => request.get('/report/packages')
