import React from 'react';

const SocialSidebar = () => {
  const links = [
    { name: 'Facebook', href: 'https://facebook.com/yourpage', color: '#1877F2', label: 'فيسبوك', icon: 'f' },
    { name: 'X', href: 'https://x.com/yourhandle', color: '#000000', label: 'إكس', icon: 'x' },
    { name: 'Instagram', href: 'https://instagram.com/yourpage', color: '#E4405F', label: 'انستقرام', icon: 'ig' },
    { name: 'WhatsApp', href: 'https://wa.me/+966500000000', color: '#25D366', label: 'واتساب', icon: 'wa' },
    { name: 'TikTok', href: 'https://www.tiktok.com/@yourhandle', color: '#010101', label: 'تيك توك', icon: 'tt' },
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




