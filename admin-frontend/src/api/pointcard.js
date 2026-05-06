import request from '@/utils/request'
export const getBatches = () => request.get('/point-cards/batches')
export const createBatch = (data) => request.post('/point-cards/batches', data)
export const getCards = (batchId) => request.get(`/point-cards/batches/${batchId}/cards`)
export const generateCards = (batchId, data) => request.post(`/point-cards/batches/${batchId}/generate`, data)
export const exportCards = (batchId) => request.get(`/point-cards/batches/${batchId}/export`)
