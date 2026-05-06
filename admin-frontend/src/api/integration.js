import request from '@/utils/request'

// 万能会员整合配置
export const getConfigs = () => request.get('/integration/configs')
export const createConfig = (data) => request.post('/integration/config', data)
export const updateConfig = (id, data) => request.put(`/integration/config/${id}`, data)
export const deleteConfig = (id) => request.delete(`/integration/config/${id}`)
