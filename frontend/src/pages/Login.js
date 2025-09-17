import React, { useState } from 'react';
import { useNavigate, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Login = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [message, setMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  // Redirect if already logged in
  if (isAuthenticated()) {
    return <Navigate to="/" replace />;
  }

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setMessage('');

    try {
      const result = await login(formData.email, formData.password);

      if (result.success) {
        setMessage('تم تسجيل الدخول بنجاح');
        setTimeout(() => {
          navigate('/');
        }, 800);
      } else {
        setMessage(result.error);
      }
    } catch (error) {
      setMessage('حدث خطأ في تسجيل الدخول');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div style={{
      padding: '4rem 0',
      minHeight: '70vh',
      background: 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)',
      display: 'flex',
      alignItems: 'center'
    }}>
      <div className="container">
        <div style={{ maxWidth: '450px', margin: '0 auto' }}>
          <div style={{
            background: 'white',
            borderRadius: '16px',
            padding: '3rem',
            boxShadow: '0 10px 40px rgba(0,0,0,0.1)',
            border: '1px solid #f1f5f9'
          }}>
            <div style={{ textAlign: 'center', marginBottom: '2.5rem' }}>
              <div style={{
                fontSize: '2.5rem',
                marginBottom: '1rem',
                color: '#1e3a8a'
              }}>
                🔐
              </div>
              <h2 style={{
                color: '#1e3a8a',
                fontSize: '2rem',
                fontFamily: 'Amiri, serif',
                marginBottom: '0.5rem'
              }}>
                تسجيل الدخول
              </h2>
              <p style={{ color: '#64748b', fontSize: '0.95rem' }}>
                سجّل دخولك لمتابعة التسوق وإدارة طلباتك
              </p>
            </div>

            <form onSubmit={handleSubmit} className="modern-form" style={{ padding: 0, boxShadow: 'none', marginBottom: 0 }}>
              <div className="form-group">
                <label style={{ fontWeight: '500', color: '#374151' }}>البريد الإلكتروني</label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  placeholder="أدخل بريدك الإلكتروني"
                  style={{
                    marginTop: '0.5rem',
                    fontSize: '1rem'
                  }}
                />
              </div>

              <div className="form-group">
                <label style={{ fontWeight: '500', color: '#374151' }}>كلمة المرور</label>
                <input
                  type="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  required
                  placeholder="أدخل كلمة المرور"
                  style={{
                    marginTop: '0.5rem',
                    fontSize: '1rem'
                  }}
                />
              </div>

              <button
                type="submit"
                className="btn btn-primary"
                disabled={isLoading}
                style={{
                  width: '100%',
                  padding: '14px 28px',
                  fontSize: '1.1rem',
                  marginTop: '1rem'
                }}
              >
                {isLoading ? (
                  <span style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem' }}>
                    <div className="spinner" style={{ width: '16px', height: '16px' }}></div>
                    جاري التسجيل...
                  </span>
                ) : (
                  'تسجيل الدخول'
                )}
              </button>
            </form>

            {message && (
              <div style={{
                marginTop: '1.5rem',
                padding: '12px 16px',
                backgroundColor: message.includes('نجاح') ? '#dcfce7' : '#fef2f2',
                color: message.includes('نجاح') ? '#166534' : '#dc2626',
                borderRadius: '8px',
                textAlign: 'center',
                border: message.includes('نجاح') ? '1px solid #bbf7d0' : '1px solid #fecaca',
                fontSize: '0.95rem'
              }}>
                {message}
              </div>
            )}

            {/* Removed trial credentials box */}

            <div style={{
              textAlign: 'center',
              marginTop: '1.5rem',
              paddingTop: '1.5rem',
              borderTop: '1px solid #e2e8f0'
            }}>
              <p style={{ color: '#64748b', fontSize: '0.85rem', margin: 0 }}>
                ليس لديك حساب؟ <span onClick={() => navigate('/signup')} style={{ color: '#3b82f6', cursor: 'pointer' }}>إنشاء حساب</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;