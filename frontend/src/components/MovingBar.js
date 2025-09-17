import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';

const MovingBar = React.memo(() => {
  const [text, setText] = useState('');

  useEffect(() => {
    let intervalId;

    const loadMovingBarText = async () => {
      try {
        // Prefer dedicated endpoint; add cache-busting query param
        const res = await apiService.getMovingBarText(`?t=${Date.now()}`);
        const data = res?.data || {};
        const value = data.text || data.moving_bar_text || data?.settings?.moving_bar_text || '';
        setText(value);
      } catch (error) {
        console.error('Error loading moving bar text:', error);
        setText('');
      }
    };

    loadMovingBarText();
    // Periodically refresh in case it changes from admin (lightweight)
    intervalId = setInterval(loadMovingBarText, 60000);

    return () => {
      if (intervalId) clearInterval(intervalId);
    };
  }, []);

  // Don't render if no text
  if (!text) {
    return null;
  }

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
