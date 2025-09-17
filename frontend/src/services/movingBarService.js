import { apiService } from './api';

export const movingBarService = {
  getMovingBarText: async () => {
    return apiService.get('/moving_bar');
  },

  updateMovingBarText: async (text) => {
    return apiService.put('/moving_bar', { text });
  },
};
