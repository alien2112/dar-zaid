import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet-async';
import TeamPhotosSlider from '../components/TeamPhotosSlider';
import { FaBook, FaPen, FaGlobe, FaStar, FaBookOpen, FaHandshake, FaBullseye, FaMagic, FaLightbulb, FaChartLine } from 'react-icons/fa';
import { BsStars } from 'react-icons/bs';
import { IoEyeSharp } from 'react-icons/io5';

const About = () => {
  const [visibleSections, setVisibleSections] = useState(new Set());
  const [stats, setStats] = useState({
    books: 0,
    authors: 0,
    platforms: 0,
    years: 0
  });
  const [isAnimating, setIsAnimating] = useState(false);

  useEffect(() => {
    // Animate stats on load with counting effect
    const animateStats = () => {
      setIsAnimating(true);
      const targetValues = {
        books: 500,
        authors: 150,
        platforms: 100,
        years: 4
      };

      // Animate books (500)
      const booksInterval = setInterval(() => {
        setStats(prev => ({
          ...prev,
          books: Math.min(prev.books + 10, targetValues.books)
        }));
      }, 30);

      // Animate authors (150)
      const authorsInterval = setInterval(() => {
        setStats(prev => ({
          ...prev,
          authors: Math.min(prev.authors + 3, targetValues.authors)
        }));
      }, 40);

      // Animate platforms (100)
      const platformsInterval = setInterval(() => {
        setStats(prev => ({
          ...prev,
          platforms: Math.min(prev.platforms + 2, targetValues.platforms)
        }));
      }, 50);

      // Animate years (4)
      const yearsInterval = setInterval(() => {
        setStats(prev => ({
          ...prev,
          years: Math.min(prev.years + 0.1, targetValues.years)
        }));
      }, 100);

      // Clean up intervals after animation completes
      setTimeout(() => {
        clearInterval(booksInterval);
        clearInterval(authorsInterval);
        clearInterval(platformsInterval);
        clearInterval(yearsInterval);
        setIsAnimating(false);
      }, 3000);

      return () => {
        clearInterval(booksInterval);
        clearInterval(authorsInterval);
        clearInterval(platformsInterval);
        clearInterval(yearsInterval);
      };
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
    { label: 'كتاب منشور', value: Math.floor(stats.books), suffix: '+', icon: <FaBook className="stat-icon-svg" /> },
    { label: 'مؤلف شريك', value: Math.floor(stats.authors), suffix: '+', icon: <FaPen className="stat-icon-svg" /> },
    { label: 'منصة توزيع', value: Math.floor(stats.platforms), suffix: '+', icon: <FaGlobe className="stat-icon-svg" /> },
    { label: 'سنوات من الخبرة', value: isAnimating ? stats.years.toFixed(1) : Math.floor(stats.years), suffix: '+', icon: <FaStar className="stat-icon-svg" /> }
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
          <div className="floating-book"><FaBookOpen className="floating-icon" /></div>
          <div className="floating-pen"><FaPen className="floating-icon" /></div>
          <div className="floating-star"><BsStars className="floating-icon" /></div>
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
                <div className="book book-1"><FaBook className="book-icon" /></div>
                <div className="book book-2"><FaBookOpen className="book-icon" /></div>
                <div className="book book-3"><FaBook className="book-icon" /></div>
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
              <div className="partnership-icon"><FaHandshake className="partnership-icon-svg" /></div>
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
            <div className="vision-icon"><IoEyeSharp className="vision-icon-svg" /></div>
            <h3 className="vision-title">رؤيتنا</h3>
            <p className="vision-text">
              أن نكون الدار الرائدة في صناعة النشر في المملكة العربية السعودية، 
              والوجهة الأولى للمؤلفين والقراء على حد سواء.
            </p>
          </div>
          <div className="mission-card">
            <div className="mission-icon"><BsStars className="mission-icon-svg" /></div>
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
              <div className="value-icon"><FaChartLine className="value-icon-svg" /></div>
              <h4>التميز</h4>
              <p>نسعى للتميز في كل ما نقدمه من خدمات</p>
            </div>
            <div className="value-card">
              <div className="value-icon"><FaHandshake className="value-icon-svg" /></div>
              <h4>الشراكة</h4>
              <p>نؤمن بقوة الشراكة والتعاون</p>
            </div>
            <div className="value-card">
              <div className="value-icon"><FaLightbulb className="value-icon-svg" /></div>
              <h4>الإبداع</h4>
              <p>نشجع الإبداع والابتكار في كل عمل</p>
            </div>
            <div className="value-card">
              <div className="value-icon"><FaBullseye className="value-icon-svg" /></div>
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
