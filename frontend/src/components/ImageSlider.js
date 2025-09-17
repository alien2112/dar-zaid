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
        // Fallback to default images if API fails
        setSliderImages([
          { id: 1, title: 'مرحباً بكم', image_url: '/images/slider/1.jpg' },
          { id: 2, title: 'مجموعة الكتب', image_url: '/images/slider/2.jpg' },
          { id: 3, title: 'خدمات النشر', image_url: '/images/slider/3.jpg' }
        ]);
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
          <div key={slide.id}>
            {slide.link_url ? (
              <a href={slide.link_url} target="_blank" rel="noopener noreferrer">
                <img
                  src={slide.image_url}
                  alt={slide.title}
                  style={{ width: "100%", height: "400px", objectFit: "cover" }}
                />
              </a>
            ) : (
              <img
                src={slide.image_url}
                alt={slide.title}
                style={{ width: "100%", height: "400px", objectFit: "cover" }}
              />
            )}
            {slide.title && (
              <div className="slider-caption" style={{
                position: "absolute",
                bottom: "20px",
                left: "20px",
                color: "white",
                background: "rgba(0,0,0,0.7)",
                padding: "10px",
                borderRadius: "5px"
              }}>
                <h3>{slide.title}</h3>
                {slide.subtitle && <p>{slide.subtitle}</p>}
              </div>
            )}
          </div>
        ))}
      </Slider>
    </div>
  );
};

export default ImageSlider;
