import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { apiService } from '../services/api';

const BlogPostDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchPost();
  }, [id]);

  const fetchPost = async () => {
    try {
      setLoading(true);
      const response = await apiService.getBlogPost(id);
      setPost(response.data.post);
    } catch (error) {
      console.error('Error fetching blog post:', error);
      setError('المقال غير موجود أو تم حذفه');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">جاري تحميل المقال...</div>
      </div>
    );
  }

  if (error || !post) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>📝</div>
          <h2 style={{ color: '#ef4444', marginBottom: '1rem' }}>المقال غير موجود</h2>
          <p style={{ marginBottom: '2rem', color: '#6b7280' }}>
            {error || 'المقال المطلوب غير موجود أو تم حذفه'}
          </p>
          <Link 
            to="/blog" 
            className="btn btn-primary"
            style={{ textDecoration: 'none' }}
          >
            العودة للمدونة
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div style={{ padding: '2rem 0' }}>
      <div className="container">
        {/* Back button */}
        <div style={{ marginBottom: '2rem' }}>
          <button 
            onClick={() => navigate(-1)}
            className="btn btn-outline-secondary"
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.5rem',
              padding: '0.5rem 1rem',
              fontSize: '0.9rem'
            }}
          >
            ← العودة
          </button>
        </div>

        <article style={{ 
          maxWidth: '800px', 
          margin: '0 auto',
          padding: '0 1rem'
        }}>
          {/* Post image */}
          <img 
            src={post.image || '/images/blog-placeholder.jpg'} 
            alt={post.title} 
            style={{ 
              width: '100%', 
              height: '300px', 
              objectFit: 'cover', 
              marginBottom: '2rem',
              borderRadius: '12px',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            }} 
          />
          
          {/* Post title */}
          <h1 style={{ 
            marginBottom: '1.5rem', 
            color: '#1e3a8a',
            fontSize: 'clamp(1.5rem, 4vw, 2.2rem)',
            lineHeight: 1.3,
            fontFamily: 'Amiri, serif',
            textAlign: 'center'
          }}>
            {post.title}
          </h1>
          
          {/* Post meta */}
          <div style={{ 
            display: 'flex', 
            flexDirection: 'column',
            gap: '0.5rem',
            marginBottom: '2rem',
            color: '#6b7280',
            fontSize: '0.9rem',
            textAlign: 'center',
            padding: '1rem',
            backgroundColor: '#f8fafc',
            borderRadius: '8px',
            border: '1px solid #e2e8f0'
          }}>
            <span><strong>بواسطة:</strong> {post.author}</span>
            <span><strong>تاريخ النشر:</strong> {new Date(post.date).toLocaleDateString('ar-SA')}</span>
            <span><strong>عدد المشاهدات:</strong> {post.views}</span>
          </div>
          
          {/* Post content */}
          <div 
            style={{ 
              lineHeight: '1.8', 
              color: '#374151',
              fontSize: '1.1rem',
              textAlign: 'right',
              direction: 'rtl'
            }}
            dangerouslySetInnerHTML={{ __html: post.content }}
          />
          
          {/* Back to blog button */}
          <div style={{ 
            marginTop: '3rem', 
            textAlign: 'center',
            padding: '2rem 0',
            borderTop: '1px solid #e5e7eb'
          }}>
            <Link 
              to="/blog" 
              className="btn btn-primary"
              style={{ 
                textDecoration: 'none',
                padding: '12px 24px',
                fontSize: '1rem'
              }}
            >
              العودة للمدونة
            </Link>
          </div>
        </article>
      </div>
    </div>
  );
};

export default BlogPostDetail;
