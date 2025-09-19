import React, { useState, useEffect } from "react";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { apiService } from "../services/api";

const ImageSlider = () => {
  const [sliderImages, setSliderImages] = useState([]);
  const [loading, setLoading] = useState(true);

  const getSliderSettings = () => ({
    rtl: true,
    dots: true,
    infinite: sliderImages.length > 1,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: sliderImages.length > 1,
    autoplaySpeed: 4000,
    adaptiveHeight: false,
    arrows: true,
    pauseOnHover: true,
    swipeToSlide: true,
    touchMove: true,
    accessibility: true,
    centerMode: false,
    variableWidth: false,
    responsive: [
      {
        breakpoint: 768,
        settings: {
          dots: true,
          arrows: false,
          autoplaySpeed: 5000,
        }
      },
      {
        breakpoint: 480,
        settings: {
          dots: true,
          arrows: false,
          autoplaySpeed: 6000,
        }
      }
    ]
  });

  useEffect(() => {
    const loadSliderImages = async () => {
      try {
        console.log('Loading slider images...');
        const response = await apiService.getSliderImages(false); // Get only active images
        console.log('Slider response:', response);

        if (response && response.data && response.data.sliders) {
          setSliderImages(response.data.sliders);
          console.log('Loaded slider images:', response.data.sliders.length);
        } else {
          console.log('No slider data found in response');
          setSliderImages([]);
        }
      } catch (error) {
        console.error('Error loading slider images:', error);
        console.error('Error details:', error.response || error.message);
        setSliderImages([]);
      } finally {
        setLoading(false);
      }
    };

    loadSliderImages();
  }, []);

  const getSliderHeight = () => {
    if (typeof window !== 'undefined') {
      if (window.innerWidth <= 480) return "250px";
      if (window.innerWidth <= 768) return "320px";
      if (window.innerWidth <= 1024) return "400px";
      return "450px";
    }
    return "400px";
  };

  if (loading) {
    return (
      <div className="image-slider" style={{ height: getSliderHeight(), display: "flex", alignItems: "center", justifyContent: "center" }}>
        <div>جاري التحميل...</div>
      </div>
    );
  }

  if (sliderImages.length === 0) {
    return (
      <div className="image-slider" style={{ height: getSliderHeight(), display: "flex", alignItems: "center", justifyContent: "center" }}>
        <div>لا توجد صور متاحة</div>
      </div>
    );
  }

  return (
    <div className="image-slider" style={{
      overflow: 'hidden',
      position: 'relative',
      background: 'transparent',
      zIndex: 1
    }}>
      <Slider {...getSliderSettings()}>
        {sliderImages.map((slide) => (
          <div key={slide.id} className="slider-item" style={{ position: 'relative' }}>
            {slide.link_url ? (
              <a href={slide.link_url} target="_blank" rel="noopener noreferrer">
                <img
                  src={slide.image_url}
                  alt={slide.title || 'Slider image'}
                  style={{ width: "100%", height: getSliderHeight(), objectFit: "cover" }}
                  onError={(e) => {
                    console.error('Failed to load image:', slide.image_url);
                    e.target.style.display = 'none';
                  }}
                />
              </a>
            ) : (
              <img
                src={slide.image_url}
                alt={slide.title || 'Slider image'}
                style={{ width: "100%", height: getSliderHeight(), objectFit: "cover" }}
                onError={(e) => {
                  console.error('Failed to load image:', slide.image_url);
                  e.target.style.display = 'none';
                }}
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
                padding: window.innerWidth <= 480 ? "12px 16px" : "20px 30px",
                textAlign: 'center'
              }}>
                <h3 style={{
                  margin: 0,
                  fontSize: window.innerWidth <= 480 ? '1.2rem' : window.innerWidth <= 768 ? '1.8rem' : '2.2rem',
                  textShadow: '0 2px 6px rgba(0,0,0,0.6)',
                  fontWeight: 'bold'
                }}>
                  {slide.title}
                </h3>
                {slide.subtitle && (
                  <p style={{
                    margin: '8px 0 0',
                    opacity: 0.95,
                    fontSize: window.innerWidth <= 480 ? '0.9rem' : window.innerWidth <= 768 ? '1.1rem' : '1.3rem'
                  }}>
                    {slide.subtitle}
                  </p>
                )}
              </div>
            )}
          </div>
        ))}
      </Slider>
    </div>
  );
};

export default ImageSlider;
