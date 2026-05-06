import request from '@/utils/request'

// 数据库备份管理
export const getBackupList = () => request.get('/db/list')
export const createBackup = () => request.post('/db/backup')
export const restoreBackup = (file) => request.post('/db/restore', { file })
export const deleteBackup = (id) => request.delete(`/db/backup/${id}`)
export const optimizeTables = () => request.post('/db/optimize')
export const repairTables = () => request.post('/db/repair')
