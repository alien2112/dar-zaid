import axios from 'axios';

// Native PHP API base
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const client = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const orderService = {
  createOrder: async ({ customerInfo, items, paymentMethod, total }) => {
    const payload = { customerInfo, items, paymentMethod, total };
    const res = await client.post('/orders', payload);
    return res.data;
  },

  initializePayment: async ({ payment_method, amount, currency, order_id, customer_info }) => {
    const payload = { payment_method, amount, currency, order_id, customer_info };
    const res = await client.post('/payments/initialize', payload);
    return res.data;
  }
};

export default orderService;


