import request from '@/utils/request'
export const login = (data) => request.post('/auth/login', data)
export const getAdminInfo = () => request.get('/auth/me')
export const logout = () => request.post('/auth/logout')
