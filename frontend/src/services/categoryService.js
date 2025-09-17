import { apiService } from './api';

export const categoryService = {
  getCategories: async () => {
    return apiService.get('/categories');
  },

  addCategory: async (category) => {
    return apiService.post('/categories', category);
  },

  updateCategory: async (id, payload) => {
    return apiService.put(`/categories/${id}`, payload);
  },

  deleteCategory: async (id) => {
    return apiService.delete(`/categories/${id}`);
  },
};
