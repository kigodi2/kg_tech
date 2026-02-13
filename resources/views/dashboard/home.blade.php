@extends('layout')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
  /* Hero Carousel Section */
  .heroSwiper {
    position: relative;
    width: 100vw;
    height: 500px;
    margin-top: -20px;
    margin-bottom: 0;
    margin-left: calc(-50vw + 50%);
  }

  .heroSwiper .swiper-slide {
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    height: 500px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    width: 100%;
  }

  .carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%);
    z-index: 1;
  }

  .carousel-container {
    position: relative;
    z-index: 2;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .carousel-content {
    text-align: center;
    color: white;
    max-width: 800px;
    padding: 0 20px;
  }

  .carousel-content h2 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
  }

  .highlight {
    color: #FFD700;
  }

  .carousel-content p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.95);
    margin: 0 0 1.5rem 0;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
  }

  .btn-get-started {
    display: inline-block;
    padding: 12px 32px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: 2px solid #667eea;
  }

  .btn-get-started:hover {
    background: #5568d3;
    border-color: #5568d3;
    text-decoration: none;
    color: white;
  }

  /* Swiper Pagination - Small Circles */
  .heroSwiper .swiper-pagination {
    bottom: 20px !important;
    z-index: 10;
  }

  .heroSwiper .swiper-pagination-bullet {
    width: 10px !important;
    height: 10px !important;
    background-color: rgba(255, 255, 255, 0.6) !important;
    margin: 0 6px !important;
    transition: all 0.3s ease !important;
  }

  .heroSwiper .swiper-pagination-bullet-active {
    background-color: white !important;
    width: 12px !important;
    height: 12px !important;
  }

  /* Swiper Navigation Arrows */
  .heroSwiper .swiper-button-prev,
  .heroSwiper .swiper-button-next {
    background: rgba(0, 0, 0, 0.5);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    color: white;
    transition: all 0.3s ease;
    z-index: 10;
  }

  .heroSwiper .swiper-button-prev::after,
  .heroSwiper .swiper-button-next::after {
    font-size: 24px;
  }

  .heroSwiper .swiper-button-prev:hover,
  .heroSwiper .swiper-button-next:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
  }

  /* Events and News Section */
  .about-us {
    padding: 60px 20px;
    background: white;
    width: 100vw;
    margin-left: calc(-50vw + 50%);
  }

  .about-us .content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
  }

  .about-us h2 {
    font-size: 1.6rem;
    color: #212529;
    font-weight: 600;
    margin-bottom: 1.2rem;
  }

  .read-details {
    display: inline-block;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    margin-top: 1rem;
    transition: all 0.3s ease;
  }

  .read-details:hover {
    color: #5568d3;
    text-decoration: underline;
  }

  .about-us a {
    text-decoration: none !important;
  }

  .about-us a:hover {
    text-decoration: none !important;
  }

  .about-us a:hover .news {
    color: #667eea;
  }

  .news {
    display: flex;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    transition: all 0.3s ease;
    text-decoration: none;
    border-bottom: none;
  }

  .calendar-container {
    display: flex;
    margin-right: 1rem;
    flex-shrink: 0;
  }

  .calender-left {
    background: linear-gradient(135deg, #667eea 0%, #5568d3 100%);
    color: white;
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 20px;
    font-weight: bold;
    min-width: 55px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .calender-right {
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-left: 8px;
  }

  .day {
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
  }

  .month {
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
  }

  .news p {
    margin: 0;
    color: #495057;
    font-weight: 500;
    font-size: 14px;
  }

  /* Services Section */
  .services {
    padding: 60px 20px;
    background: #f8f9fa;
    width: 100vw;
    margin-left: calc(-50vw + 50%);
  }

  .section-bg {
    background-color: #f8f9fa;
  }

  .services-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .services-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
  }

  .icon-box {
    background: white;
    padding: 40px 30px;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
    text-align: center;
    position: relative;
  }

  .icon-box:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
  }

  .icon-box .icon {
    margin: 0 auto 20px;
    width: 90px;
    height: 90px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 45% 55% 52% 48% / 48% 45% 55% 52%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .icon-box .icon i {
    font-size: 45px;
    position: relative;
    color: white;
    transition: transform 0.3s ease, filter 0.3s ease;
  }

  .icon-box:hover .icon {
    transform: scale(1.2) rotateZ(-5deg);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  }

  .icon-box:hover .icon i {
    transform: scale(1.1);
  }

  .icon-box h4 {
    font-size: 1.2rem;
    margin: 20px 0 15px;
    font-weight: 600;
    color: #212529;
  }

  .icon-box p {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.6;
  }

  /* Icon Box Colors */
  .iconbox-blue .icon {
    background: linear-gradient(135deg, #667eea 0%, #5568d3 100%);
  }

  .iconbox-orange .icon {
    background: linear-gradient(135deg, #fd7e14 0%, #e67e22 100%);
  }

  .iconbox-pink .icon {
    background: linear-gradient(135deg, #e83e8c 0%, #d03a7d 100%);
  }

  .iconbox-green .icon {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
  }

  .iconbox-red .icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  }

  .iconbox-purple .icon {
    background: linear-gradient(135deg, #6f42c1 0%, #5c35a6 100%);
  }

  /* Card entrance animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .icon-box {
    animation: fadeInUp 0.7s ease-out forwards;
  }

  .icon-box:nth-child(1) {
    animation-delay: 0.1s;
  }

  .icon-box:nth-child(2) {
    animation-delay: 0.2s;
  }

  .icon-box:nth-child(3) {
    animation-delay: 0.3s;
  }

  .icon-box:nth-child(4) {
    animation-delay: 0.4s;
  }

  .icon-box:nth-child(5) {
    animation-delay: 0.5s;
  }

  .icon-box:nth-child(6) {
    animation-delay: 0.6s;
  }

  @media (max-width: 1024px) {
    .services-row {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 768px) {
    .carousel-item {
      height: 280px;
      background-attachment: scroll;
    }

    .carousel-content h2 {
      font-size: 1.8rem;
    }

    .carousel-content p {
      font-size: 1rem;
    }

    .about-us .content {
      grid-template-columns: 1fr;
      gap: 2rem;
    }

    .services-row {
      grid-template-columns: 1fr;
    }

    .news {
      flex-direction: column;
    }

    .calendar-container {
      margin-right: 0;
      margin-bottom: 1rem;
    }
  }
</style>

<!-- Hero Carousel Section with Swiper -->
<div class="swiper heroSwiper" id="heroCarousel">
  <div class="swiper-wrapper">
    <!-- Slide 1: Welcome -->
    <div class="swiper-slide" style="background: linear-gradient(135deg, #667eea 0%, #5568d3 100%);">
      <div class="carousel-overlay"></div>
      <div class="carousel-container">
        <div class="carousel-content">
          <h2>Welcome to <span class="highlight">IRMS</span></h2>
          <p>Integrated Results Management System - Real Time Information, 7 days and 24 hours</p>
          <div class="text-center"><a href="/dashboard" class="btn-get-started">Read More</a></div>
        </div>
      </div>
    </div>

    <!-- Slide 2: Examination Management -->
    <div class="swiper-slide" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
      <div class="carousel-overlay"></div>
      <div class="carousel-container">
        <div class="carousel-content">
          <h2>Examination Management</h2>
          <p>Supporting quality education through comprehensive examination management and result processing</p>
          <div class="text-center"><a href="/dashboard" class="btn-get-started">Learn More</a></div>
        </div>
      </div>
    </div>

    <!-- Slide 3: Results Management -->
    <div class="swiper-slide" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
      <div class="carousel-overlay"></div>
      <div class="carousel-container">
        <div class="carousel-content">
          <h2>Results Management System</h2>
          <p>Efficient processing and publication of examination results for educational stakeholders</p>
          <div class="text-center"><a href="/dashboard" class="btn-get-started">Discover More</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <div class="swiper-pagination"></div>

  <!-- Navigation arrows -->
  <div class="swiper-button-prev carousel-arrow"></div>
  <div class="swiper-button-next carousel-arrow"></div>
</div>

<!-- Events and News Section -->
<section class="about-us">
  <div class="services-container">
    <div class="content">
      <!-- Events Column -->
      <div>
        <h2>Events</h2>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d') }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D') }}</div>
                <div class="month">{{ date('M') }}</div>
              </div>
            </div>
            <p>PSLE Examination Registration Opens</p>
            <span style="clear: both"></span>
          </div>
        </a>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d', strtotime('+7 days')) }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D', strtotime('+7 days')) }}</div>
                <div class="month">{{ date('M', strtotime('+7 days')) }}</div>
              </div>
            </div>
            <p>CSEE Examination Begins</p>
            <span style="clear: both"></span>
          </div>
        </a>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d', strtotime('+14 days')) }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D', strtotime('+14 days')) }}</div>
                <div class="month">{{ date('M', strtotime('+14 days')) }}</div>
              </div>
            </div>
            <p>Results Processing Phase Starts</p>
            <span style="clear: both"></span>
          </div>
        </a>
        <a href="#" class="read-details">All events</a>
      </div>

      <!-- News Column -->
      <div>
        <h2>News</h2>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d', strtotime('-3 days')) }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D', strtotime('-3 days')) }}</div>
                <div class="month">{{ date('M', strtotime('-3 days')) }}</div>
              </div>
            </div>
            <p>New IRMS Portal Features Launched</p>
            <span style="clear: both"></span>
          </div>
        </a>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d', strtotime('-10 days')) }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D', strtotime('-10 days')) }}</div>
                <div class="month">{{ date('M', strtotime('-10 days')) }}</div>
              </div>
            </div>
            <p>System Security Updates Completed</p>
            <span style="clear: both"></span>
          </div>
        </a>
        <a href="#" style="color: inherit">
          <div class="news">
            <div class="calendar-container">
              <div class="calender-left">{{ date('d', strtotime('-15 days')) }}</div>
              <div class="calender-right">
                <div class="day">{{ date('D', strtotime('-15 days')) }}</div>
                <div class="month">{{ date('M', strtotime('-15 days')) }}</div>
              </div>
            </div>
            <p>User Training Sessions Schedule Published</p>
            <span style="clear: both"></span>
          </div>
        </a>

        <a href="#" class="read-details">All news</a>
      </div>
    </div>
  </div>
</section>

<!-- Services Section - 6 Cards -->
<section class="services section-bg">
  <div class="services-container">
    <div class="services-row">
      <!-- Card 1: Blue - Exam Registration -->
      <div class="icon-box iconbox-blue">
        <div class="icon">
          <i class="fas fa-clipboard"></i>
        </div>
        <h4>Exam Registration</h4>
        <p>Complete examination registration and candidate enrollment for PSLE, CSEE, and ACSEE</p>
      </div>

      <!-- Card 2: Orange - Marking & Grading -->
      <div class="icon-box iconbox-orange">
        <div class="icon">
          <i class="fas fa-pen"></i>
        </div>
        <h4>Marking & Grading</h4>
        <p>Comprehensive marking system with automated grade calculation and quality assurance</p>
      </div>

      <!-- Card 3: Pink - Data Validation -->
      <div class="icon-box iconbox-pink">
        <div class="icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h4>Data Validation</h4>
        <p>Rigorous validation and verification of examination data to ensure accuracy and integrity</p>
      </div>

      <!-- Card 4: Green - Security & Privacy -->
      <div class="icon-box iconbox-green">
        <div class="icon">
          <i class="fas fa-lock"></i>
        </div>
        <h4>Security & Privacy</h4>
        <p>Advanced security protocols and data protection for confidential examination information</p>
      </div>

      <!-- Card 5: Red - Results Publication -->
      <div class="icon-box iconbox-red">
        <div class="icon">
          <i class="fas fa-file"></i>
        </div>
        <h4>Results Publication</h4>
        <p>Efficient publication of examination results to registered candidates and institutions</p>
      </div>

      <!-- Card 6: Purple - Support Services -->
      <div class="icon-box iconbox-purple">
        <div class="icon">
          <i class="fas fa-headset"></i>
        </div>
        <h4>Support Services</h4>
        <p>Comprehensive support and customer service for all examination-related inquiries</p>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  // Initialize Swiper
  const swiper = new Swiper('.heroSwiper', {
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
      dynamicBullets: false,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    speed: 800,
  });
</script>
@endsection
