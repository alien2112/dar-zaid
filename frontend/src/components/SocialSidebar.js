import React from 'react';

const SocialSidebar = () => {
  const links = [
    { name: 'X', href: 'https://x.com/darzaid22', color: '#000000', label: 'إكس', icon: 'X' },
    { name: 'Instagram', href: 'https://instagram.com/dar.zaid.2022', color: '#E4405F', label: 'انستقرام', icon: '📷' },
    { name: 'WhatsApp', href: 'https://wa.me/966561123119', color: '#25D366', label: 'واتساب', icon: '💬' },
    { name: 'TikTok', href: 'https://www.tiktok.com/@dar.zaid.2022', color: '#000000', label: 'تيك توك', icon: '♪' },
  ];

  return (
    <div className="social-sidebar" aria-label="روابط التواصل الاجتماعي">
      {links.map(link => (
        <a
          key={link.name}
          href={link.href}
          className="social-btn"
          style={{ backgroundColor: link.color }}
          target="_blank"
          rel="noopener noreferrer"
          aria-label={link.label}
          title={link.label}
        >
          <span className="social-icon-text">{link.icon.toUpperCase()}</span>
        </a>
      ))}
    </div>
  );
};

export default SocialSidebar;





