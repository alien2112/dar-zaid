import { apiService } from './api';

export const newsService = {
  list: (publishedOnly = false) => apiService.get(`/news${publishedOnly ? '?published=1' : ''}`),
  create: (payload) => apiService.post('/news', payload),
  update: (id, payload) => apiService.put(`/news/${id}`, payload),
  remove: (id) => apiService.delete(`/news/${id}`)
};












