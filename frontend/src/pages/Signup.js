import React, { useState } from 'react';
import { useNavigate, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { apiService } from '../services/api';

const Signup = () => {
  const [formData, setFormData] = useState({ name: '', email: '', password: '' });
  const [message, setMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { signup, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  if (isAuthenticated()) {
    return <Navigate to="/" replace />;
  }

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSignup = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.password) {
      setMessage('جميع الحقول مطلوبة');
      return;
    }

    setIsLoading(true);
    setMessage('');

    try {
      const response = await apiService.signup(formData);

      if (response.data.success) {
        setMessage('تم إنشاء الحساب بنجاح');
        setTimeout(() => navigate('/'), 800);
      } else {
        setMessage(response.data.error || 'فشل في إنشاء الحساب');
      }
    } catch (error) {
      setMessage(error.response?.data?.error || 'فشل في إنشاء الحساب');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div style={{ padding: '4rem 0', minHeight: '70vh', background: 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)', display: 'flex', alignItems: 'center' }}>
      <div className="container">
        <div style={{ maxWidth: '680px', margin: '0 auto' }}>
          <div style={{ background: 'white', borderRadius: '16px', padding: '3rem', boxShadow: '0 10px 40px rgba(0,0,0,0.1)', border: '1px solid #f1f5f9' }}>
            <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
              <h2 style={{ color: '#1e3a8a', fontSize: '2rem', fontFamily: 'Amiri, serif', marginBottom: '0.5rem' }}>
                إنشاء حساب
              </h2>
              <p style={{ color: '#64748b' }}>
                أنشئ حسابك لمتابعة التسوق
              </p>
            </div>

            <form onSubmit={handleSignup} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
              <div className="form-group">
                <label>الاسم الكامل</label>
                <input
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  placeholder="أدخل اسمك الكامل"
                />
              </div>
              <div className="form-group">
                <label>البريد الإلكتروني</label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  placeholder="أدخل بريدك الإلكتروني"
                />
              </div>
              <div className="form-group">
                <label>كلمة المرور</label>
                <input
                  type="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  required
                  placeholder="أدخل كلمة المرور"
                />
              </div>
              <button type="submit" className="btn btn-primary" disabled={isLoading} style={{ width: '100%', marginTop: '1rem' }}>
                {isLoading ? 'جاري إنشاء الحساب...' : 'إنشاء الحساب'}
              </button>
            </form>

            {message && (
              <div style={{ 
                marginTop: '1.5rem', 
                padding: '12px 16px', 
                backgroundColor: message.includes('نجاح') || message.includes('تم إرسال') ? '#dcfce7' : '#fef2f2', 
                color: message.includes('نجاح') || message.includes('تم إرسال') ? '#166534' : '#dc2626', 
                borderRadius: '8px', 
                textAlign: 'center', 
                border: message.includes('نجاح') || message.includes('تم إرسال') ? '1px solid #bbf7d0' : '1px solid #fecaca' 
              }}>
                {message}
              </div>
            )}

            <div style={{ textAlign: 'center', marginTop: '1.5rem' }}>
              <span style={{ color: '#64748b' }}>لديك حساب؟ </span>
              <span onClick={() => navigate('/login')} style={{ color: '#3b82f6', cursor: 'pointer' }}>تسجيل الدخول</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Signup;


