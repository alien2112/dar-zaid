import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet-async';
import TeamPhotosSlider from '../components/TeamPhotosSlider';

const About = () => {
  const [visibleSections, setVisibleSections] = useState(new Set());
  const [stats, setStats] = useState({
    books: 0,
    authors: 0,
    platforms: 0,
    years: 0
  });

  useEffect(() => {
    // Animate stats on load
    const animateStats = () => {
      setStats({
        books: 500,
        authors: 150,
        platforms: 100,
        years: 4
      });
    };

    const timer = setTimeout(animateStats, 500);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setVisibleSections(prev => new Set([...prev, entry.target.id]));
          }
        });
      },
      { threshold: 0.1 }
    );

    const sections = document.querySelectorAll('.about-section');
    sections.forEach(section => observer.observe(section));

    return () => observer.disconnect();
  }, []);

  const statsData = [
    { label: 'كتاب منشور', value: stats.books, suffix: '+', icon: '📚' },
    { label: 'مؤلف شريك', value: stats.authors, suffix: '+', icon: '✍️' },
    { label: 'منصة توزيع', value: stats.platforms, suffix: '+', icon: '🌐' },
    { label: 'سنوات من الخبرة', value: stats.years, suffix: '+', icon: '⭐' }
  ];

  return (
    <div className="about-page">
      <Helmet>
        <title>عن دار زيد للنشر والتوزيع - رؤيتنا ورسالتنا</title>
        <meta name="description" content="تعرف على دار زيد للنشر والتوزيع، رؤيتنا في إثراء المحتوى الثقافي العربي ودعم المؤلفين والمبدعين في المملكة العربية السعودية" />
        <meta name="keywords" content="دار زيد, نشر, توزيع, كتب عربية, مؤلفين, ثقافة, السعودية" />
      </Helmet>

      {/* Hero Section */}
      <div className="about-hero">
        <div className="about-hero-content">
          <h1 className="about-title">
            عن دار زيد للنشر والتوزيع
          </h1>
          <p className="about-subtitle">
            نؤمن بأن كل قلم يحمل رسالة، وكل كتاب يحمل حلم
          </p>
        </div>
        <div className="about-hero-decoration">
          <div className="floating-book">📖</div>
          <div className="floating-pen">✍️</div>
          <div className="floating-star">⭐</div>
        </div>
      </div>

      <div className="container">
        {/* Stats Section */}
        <div className="about-stats" id="stats">
          <div className="stats-grid">
            {statsData.map((stat, index) => (
              <div key={index} className="stat-card">
                <div className="stat-icon">{stat.icon}</div>
                <div className="stat-number">{stat.value}{stat.suffix}</div>
                <div className="stat-label">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Story Section */}
        <div className="about-section" id="story">
          <div className="story-content">
            <div className="story-text">
              <h2 className="section-title">قصتنا</h2>
              <p className="story-paragraph">
                تأسست دار زيد للنشر والتوزيع عام ٢٠٢٢م برؤية ثقافية طموحة تهدف إلى تمكين المؤلفين من مختلف الشرائح، 
                وتقديم الدعم المهني لنشر مؤلفاتهم بأسلوب يليق بقيمتهم الفكرية والإبداعية.
              </p>
              <p className="story-paragraph">
                ومنذ انطلاقتها، حرصت الدار على توسيع نطاق التوزيع ليشمل أكثر من ١٠٠ منصة كتب ومنافذ بيع في المنطقة الغربية، 
                مما جعلها من أبرز الجهات الفاعلة في صناعة النشر المحلي.
              </p>
            </div>
            <div className="story-visual">
              <div className="book-stack">
                <div className="book book-1">📚</div>
                <div className="book book-2">📖</div>
                <div className="book book-3">📕</div>
              </div>
            </div>
          </div>
        </div>

        {/* Team Photos Section */}
        <div className="about-section" id="team">
          <TeamPhotosSlider />
        </div>

        {/* Partnership Section */}
        <div className="about-section" id="partnership">
          <div className="partnership-content">
            <div className="partnership-visual">
              <div className="partnership-icon">🤝</div>
            </div>
            <div className="partnership-text">
              <h2 className="section-title">شراكتنا الاستراتيجية</h2>
              <p className="partnership-paragraph">
                تفخر دار زيد بكونها الشريك الحصري والاستراتيجي لمنصة المؤلف السعودي للثقافة والترفيه، 
                حيث تتكامل جهود الطرفين في تعزيز الحراك الثقافي.
              </p>
              <p className="partnership-paragraph">
                هذا التعاون يعكس التزام الدار بتعزيز الهوية الثقافية الوطنية، وتقديم محتوى أدبي ومعرفي 
                يثري المشهد الثقافي في المملكة.
              </p>
            </div>
          </div>
        </div>

        {/* Vision & Mission */}
        <div className="vision-mission">
          <div className="vision-card">
            <div className="vision-icon">🎯</div>
            <h3 className="vision-title">رؤيتنا</h3>
            <p className="vision-text">
              أن نكون الدار الرائدة في صناعة النشر في المملكة العربية السعودية، 
              والوجهة الأولى للمؤلفين والقراء على حد سواء.
            </p>
          </div>
          <div className="mission-card">
            <div className="mission-icon">💫</div>
            <h3 className="mission-title">رسالتنا</h3>
            <p className="mission-text">
              إثراء المحتوى الثقافي العربي، ودعم المؤلفين والمبدعين، 
              وتسهيل وصول أعمالهم إلى القراء في كل مكان.
            </p>
          </div>
        </div>

        {/* Values Section */}
        <div className="about-section" id="values">
          <h2 className="section-title text-center">قيمنا</h2>
          <div className="values-grid">
            <div className="value-card">
              <div className="value-icon">🌟</div>
              <h4>التميز</h4>
              <p>نسعى للتميز في كل ما نقدمه من خدمات</p>
            </div>
            <div className="value-card">
              <div className="value-icon">🤝</div>
              <h4>الشراكة</h4>
              <p>نؤمن بقوة الشراكة والتعاون</p>
            </div>
            <div className="value-card">
              <div className="value-icon">💡</div>
              <h4>الإبداع</h4>
              <p>نشجع الإبداع والابتكار في كل عمل</p>
            </div>
            <div className="value-card">
              <div className="value-icon">🎯</div>
              <h4>التركيز</h4>
              <p>نركز على تحقيق أهدافنا بوضوح</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default About;
