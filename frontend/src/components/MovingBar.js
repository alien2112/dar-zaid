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
      width: '100%',
      position: 'relative',
    }}>
      <div className="moving-bar-text">
        {text}
      </div>
      <style>
        {`
          @keyframes movingBarScroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
          }

          .moving-bar-text {
            display: inline-block;
            padding-left: 100%;
            animation: movingBarScroll 20s linear infinite !important;
          }

          /* Ensure moving bar still animates even if user prefers reduced motion */
          @media (prefers-reduced-motion: reduce) {
            .moving-bar-text {
              animation-duration: 30s !important;
            }
          }
        `}
      </style>
    </div>
  );
});

export default MovingBar;
