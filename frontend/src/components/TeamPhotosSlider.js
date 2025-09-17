import React, { useState, useEffect } from "react";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { apiService } from "../services/api";

const TeamPhotosSlider = () => {
  const [teamPhotos, setTeamPhotos] = useState([]);
  const [loading, setLoading] = useState(true);

  const settings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    arrows: true,
    fade: true,
    pauseOnHover: true,
  };

  useEffect(() => {
    const loadTeamPhotos = async () => {
      try {
        const response = await apiService.getTeamPhotos(false); // Get only active photos
        setTeamPhotos(response.data.team_photos || []);
      } catch (error) {
        console.error('Error loading team photos:', error);
        setTeamPhotos([]);
      } finally {
        setLoading(false);
      }
    };

    loadTeamPhotos();
  }, []);

  if (loading) {
    return (
      <div className="team-photos-slider" style={{ 
        height: "500px", 
        display: "flex", 
        alignItems: "center", 
        justifyContent: "center",
        background: "linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)"
      }}>
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>جاري تحميل صور الفريق...</p>
        </div>
      </div>
    );
  }

  if (teamPhotos.length === 0) {
    return (
      <div className="team-photos-slider" style={{ 
        height: "500px", 
        display: "flex", 
        alignItems: "center", 
        justifyContent: "center",
        background: "linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)"
      }}>
        <div className="no-photos">
          <div className="no-photos-icon">👥</div>
          <p>لا توجد صور متاحة للفريق</p>
        </div>
      </div>
    );
  }

  return (
    <div className="team-photos-slider">
      <div className="team-slider-header">
        <h2 className="team-slider-title">صفحات من رحلة الدار</h2>
        <p className="team-slider-subtitle">تعرف على فريق دار زيد للنشر والتوزيع</p>
      </div>
      
      <Slider {...settings}>
        {teamPhotos.map((photo) => (
          <div key={photo.id} className="team-slider-item">
            <div className="team-photo-container">
              <img
                src={photo.image_url}
                alt={photo.title}
                className="team-photo"
              />
              <div className="team-photo-overlay">
                <div className="team-photo-content">
                  <h3 className="team-photo-title">{photo.title}</h3>
                  {photo.description && (
                    <p className="team-photo-description">{photo.description}</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        ))}
      </Slider>

      <style jsx>{`
        .team-photos-slider {
          margin: 2rem 0;
          padding: 2rem 0;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          border-radius: 20px;
          overflow: hidden;
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .team-slider-header {
          text-align: center;
          margin-bottom: 2rem;
          padding: 0 2rem;
        }

        .team-slider-title {
          color: white;
          font-size: 2.5rem;
          font-weight: bold;
          margin-bottom: 0.5rem;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .team-slider-subtitle {
          color: rgba(255, 255, 255, 0.9);
          font-size: 1.2rem;
          margin: 0;
        }

        .team-slider-item {
          outline: none;
        }

        .team-photo-container {
          position: relative;
          height: 500px;
          border-radius: 15px;
          overflow: hidden;
          margin: 0 1rem;
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .team-photo {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.3s ease;
        }

        .team-photo-container:hover .team-photo {
          transform: scale(1.05);
        }

        .team-photo-overlay {
          position: absolute;
          bottom: 0;
          left: 0;
          right: 0;
          background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
          padding: 2rem;
          color: white;
        }

        .team-photo-content {
          text-align: center;
        }

        .team-photo-title {
          font-size: 1.8rem;
          font-weight: bold;
          margin: 0 0 0.5rem 0;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.7);
        }

        .team-photo-description {
          font-size: 1rem;
          margin: 0;
          opacity: 0.9;
          line-height: 1.5;
        }

        .loading-spinner {
          text-align: center;
          color: white;
        }

        .spinner {
          width: 40px;
          height: 40px;
          border: 4px solid rgba(255, 255, 255, 0.3);
          border-top: 4px solid white;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin: 0 auto 1rem;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        .no-photos {
          text-align: center;
          color: white;
        }

        .no-photos-icon {
          font-size: 3rem;
          margin-bottom: 1rem;
        }

        /* Slick slider customizations */
        .team-photos-slider .slick-dots {
          bottom: 20px;
        }

        .team-photos-slider .slick-dots li button:before {
          color: white;
          font-size: 12px;
        }

        .team-photos-slider .slick-dots li.slick-active button:before {
          color: white;
        }

        .team-photos-slider .slick-prev,
        .team-photos-slider .slick-next {
          z-index: 10;
          width: 50px;
          height: 50px;
        }

        .team-photos-slider .slick-prev:before,
        .team-photos-slider .slick-next:before {
          font-size: 30px;
          color: white;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .team-photos-slider .slick-prev {
          left: 20px;
        }

        .team-photos-slider .slick-next {
          right: 20px;
        }

        @media (max-width: 768px) {
          .team-slider-title {
            font-size: 2rem;
          }

          .team-slider-subtitle {
            font-size: 1rem;
          }

          .team-photo-container {
            height: 400px;
            margin: 0 0.5rem;
          }

          .team-photo-title {
            font-size: 1.5rem;
          }

          .team-photo-description {
            font-size: 0.9rem;
          }
        }
      `}</style>
    </div>
  );
};

export default TeamPhotosSlider;
