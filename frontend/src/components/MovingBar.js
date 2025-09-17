import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';

const MovingBar = React.memo(() => {
  const [text, setText] = useState('مرحباً بكم في دار زيد للنشر والتوزيع - شحن مجاني لطلبات أكثر من 200 ريال - خصم 15% على الطلبة والأكاديميين');

  useEffect(() => {
    const loadMovingBarText = async () => {
      try {
        const response = await apiService.getMovingBarText();
        setText(response.data.text || 'مرحباً بكم في دار زيد للنشر والتوزيع - شحن مجاني لطلبات أكثر من 200 ريال - خصم 15% على الطلبة والأكاديميين');
      } catch (error) {
        console.error('Error loading moving bar text:', error);
        // Keep default text if API fails
      }
    };

    loadMovingBarText();
  }, []);

  return (
    <div style={{
      backgroundColor: '#1e3a8a',
      color: 'white',
      padding: '0.5rem',
      overflow: 'hidden',
      whiteSpace: 'nowrap',
      width: '100%', // Ensure full width
      position: 'relative',
    }}>
      <div style={{
        display: 'inline-block',
        animation: 'move 20s linear infinite',
        paddingLeft: '100%', // Start from right edge
      }}>
        {text}
      </div>
      <style>
        {`
          @keyframes move {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-100%); }
          }
        `}
      </style>
    </div>
  );
});

export default MovingBar;
