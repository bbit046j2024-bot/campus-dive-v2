import api from './client';

export const notificationApi = {
    getNotifications: (limit = 50) => api.get(`/notifications?limit=${limit}`),
    markAsRead: (id) => api.put(`/notifications/${id}/read`),
    markAllRead: () => api.put('/notifications/read-all'),
    getUnreadCount: () => api.get('/notifications/unread-count'),
};
