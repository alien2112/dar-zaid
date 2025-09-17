import React, { useState, useEffect } from "react";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { apiService } from "../services/api";

const ImageSlider = () => {
  const [sliderImages, setSliderImages] = useState([]);
  const [loading, setLoading] = useState(true);

  const settings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 3000,
  };

  useEffect(() => {
    const loadSliderImages = async () => {
      try {
        const response = await apiService.getSliderImages(false); // Get only active images
        setSliderImages(response.data.sliders || []);
      } catch (error) {
        console.error('Error loading slider images:', error);
        // No fallback data - show empty state
        setSliderImages([]);
      } finally {
        setLoading(false);
      }
    };

    loadSliderImages();
  }, []);

  if (loading) {
    return (
      <div className="image-slider" style={{ height: "400px", display: "flex", alignItems: "center", justifyContent: "center" }}>
        <div>جاري التحميل...</div>
      </div>
    );
  }

  if (sliderImages.length === 0) {
    return (
      <div className="image-slider" style={{ height: "400px", display: "flex", alignItems: "center", justifyContent: "center" }}>
        <div>لا توجد صور متاحة</div>
      </div>
    );
  }

  return (
    <div className="image-slider">
      <Slider {...settings}>
        {sliderImages.map((slide) => (
          <div key={slide.id} className="slider-item" style={{ position: 'relative' }}>
            {slide.link_url ? (
              <a href={slide.link_url} target="_blank" rel="noopener noreferrer">
                <img
                  src={slide.image_url}
                  alt={slide.title}
                  style={{ width: "100%", height: "400px", objectFit: "cover", transform: 'scale(1.03)', transition: 'transform 6s ease', }}
                />
              </a>
            ) : (
              <img
                src={slide.image_url}
                alt={slide.title}
                style={{ width: "100%", height: "400px", objectFit: "cover", transform: 'scale(1.03)', transition: 'transform 6s ease' }}
              />
            )}
            {/* Gradient overlay for better text contrast */}
            <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(180deg, rgba(0,0,0,0.3) 20%, rgba(0,0,0,0.55) 85%)' }} />
            {slide.title && (
              <div className="slider-caption" style={{
                position: "absolute",
                bottom: 0,
                right: 0,
                left: 0,
                color: "white",
                padding: "16px 24px",
                textAlign: 'center'
              }}>
                <h3 style={{ margin: 0, fontSize: '1.6rem', textShadow: '0 2px 6px rgba(0,0,0,0.6)' }}>{slide.title}</h3>
                {slide.subtitle && <p style={{ margin: '6px 0 0', opacity: 0.95 }}>{slide.subtitle}</p>}
              </div>
            )}
          </div>
        ))}
      </Slider>
    </div>
  );
};

export default ImageSlider;
