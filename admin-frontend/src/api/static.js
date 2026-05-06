import request from '@/utils/request'

// 静态化生成管理
export const getStaticStatus = () => request.get('/static/status')
export const generateStatic = (type, id = null) => request.post('/static/generate', { type, id })
