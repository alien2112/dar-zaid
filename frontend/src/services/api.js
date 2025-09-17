import axios from 'axios';

// Prefer explicit backend URL in dev to avoid proxy issues
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

export const apiService = {
  get: (url) => api.get(url),
  post: (url, data) => api.post(url, data),
  put: (url, data) => api.put(url, data),
  delete: (url) => api.delete(url),

  // Get publishing packages
  getPackages: (admin = false) => api.get(`/packages${admin ? '?admin=true' : ''}`),
  addPackage: (packageData) => api.post('/packages', packageData),
  updatePackage: (packageId, packageData) => api.put(`/packages/${packageId}`, packageData),
  deletePackage: (packageId) => api.delete(`/packages/${packageId}`),
  
  // Get books (paginated)
  getBooks: (params) => api.get('/books', { params }),
  addBook: (bookData) => api.post('/books', bookData),
  updateBook: (bookId, bookData) => api.put(`/books/${bookId}`, bookData),
  deleteBook: (bookId) => api.delete(`/books/${bookId}`),
  
  // Get blog posts
  getBlogPosts: (admin = false) => api.get(`/blog${admin ? '?admin=true' : ''}`),
  addBlogPost: (postData) => api.post('/blog', postData),
  updateBlogPost: (postId, postData) => api.put(`/blog/${postId}`, postData),
  deleteBlogPost: (postId) => api.delete(`/blog/${postId}`),
  
  // Get categories
  getCategories: () => api.get('/categories'),
  addCategory: (categoryData) => api.post('/categories', categoryData),
  deleteCategory: (categoryId) => api.delete(`/categories/${categoryId}`),

  // Dynamic categories
  getDynamicCategories: () => api.get('/dynamic_categories'),
  getDynamicCategoryBooks: (categoryId) => api.get(`/dynamic_categories/${categoryId}/books`),
  addDynamicCategory: (categoryData) => api.post('/dynamic_categories', categoryData),
  updateDynamicCategory: (categoryId, categoryData) => api.put(`/dynamic_categories/${categoryId}`, categoryData),
  deleteDynamicCategory: (categoryId) => api.delete(`/dynamic_categories/${categoryId}`),

  // Moving Bar
  getMovingBarText: () => api.get('/moving_bar'),
  updateMovingBarText: (text) => api.put('/moving_bar', { text }),

  // Reviews
  getReviews: (bookId) => api.get(`/reviews`, { params: { book_id: bookId } }),
  addReview: (payload) => api.post(`/reviews`, payload),

  // Slider images (admin helpers)
  getSliderImages: (admin = false) => api.get(`/slider${admin ? '?admin=1' : ''}`),
  addSliderImage: (payload) => api.post('/slider', payload),
  updateSliderImage: (id, payload) => api.put(`/slider/${id}`, payload),
  deleteSliderImage: (id) => api.delete(`/slider/${id}`),

  // Dynamic categories (admin helpers)
  getDynamicCategories: () => api.get('/dynamic_categories'),
  addDynamicCategory: (payload) => api.post('/dynamic_categories', payload),
  updateDynamicCategory: (id, payload) => api.put(`/dynamic_categories/${id}`, payload),
  deleteDynamicCategory: (id) => api.delete(`/dynamic_categories/${id}`),

  // Blog posts (admin helpers)
  getBlogPosts: (admin = false) => api.get(`/blog${admin ? '?admin=1' : ''}`),
  addBlogPost: (payload) => api.post('/blog', payload),
  updateBlogPost: (id, payload) => api.put(`/blog/${id}`, payload),
  deleteBlogPost: (id) => api.delete(`/blog/${id}`),

  // Book of the Week
  getBookOfWeek: () => api.get('/book_of_week'),
  setBookOfWeek: (payload) => api.post('/book_of_week', payload),
  removeBookOfWeek: () => api.delete('/book_of_week'),

  // Payments
  initializePayment: (paymentData) => api.post('/payments/initialize', paymentData),
  processPayment: (paymentData) => api.post('/payments/process', paymentData),
  verifyPayment: (transactionId) => api.get(`/payments/verify/${transactionId}`),
  handlePaymentCallback: (callbackData) => api.post('/payments/callback', callbackData),
  refundPayment: (refundData) => api.post('/payments/refund', refundData),
  getPaymentHistory: (params) => api.get('/payments/history', { params }),

  // Uploads
  uploadImage: (formData) => api.post('/upload', formData, { headers: { 'Content-Type': 'multipart/form-data' } }),

  // Send contact form
  sendContact: (data) => api.post('/contact', data),

  // Authentication
  login: (credentials) => api.post('/auth', credentials),
  signup: (payload) => api.post('/signup', payload).catch((err) => { throw err; }),

  // Reports
  getReports: (type) => api.get(`/reports?type=${type}`),

  // Slider Images
  getSliderImages: (admin = false) => api.get(`/slider${admin ? '?admin=true' : ''}`),
  addSliderImage: (sliderData) => api.post('/slider', sliderData),
  updateSliderImage: (sliderId, sliderData) => api.put(`/slider/${sliderId}`, sliderData),
  deleteSliderImage: (sliderId) => api.delete(`/slider/${sliderId}`),

  // Book of the Week
  getBookOfWeek: () => api.get('/book_of_week'),
  setBookOfWeek: (bookData) => api.post('/book_of_week', bookData),
  updateBookOfWeek: (featuredId, bookData) => api.put(`/book_of_week/${featuredId}`, bookData),
  removeBookOfWeek: () => api.delete('/book_of_week'),
};

export default api;
