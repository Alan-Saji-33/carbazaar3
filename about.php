<?php
session_start();
require 'db-config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <section class="about-section">
            <h2 class="section-title">About CarBazaar</h2>
            
            <div class="about-content">
                <div class="about-image">
                    <img src="images/about-us.jpg" alt="About CarBazaar">
                </div>
                
                <div class="about-text">
                    <h3>Our Story</h3>
                    <p>Founded in 2023, CarBazaar is India's fastest growing used car marketplace. Our mission is to make buying and selling used cars simple, transparent, and trustworthy.</p>
                    
                    <h3>Why Choose Us?</h3>
                    <ul class="about-features">
                        <li><i class="fas fa-check-circle"></i> Verified sellers and cars</li>
                        <li><i class="fas fa-check-circle"></i> Transparent pricing</li>
                        <li><i class="fas fa-check-circle"></i> Secure transactions</li>
                        <li><i class="fas fa-check-circle"></i> Dedicated customer support</li>
                        <li><i class="fas fa-check-circle"></i> Nationwide network</li>
                    </ul>
                    
                    <h3>Our Team</h3>
                    <p>We're a team of car enthusiasts and technology experts working together to revolutionize the used car industry in India.</p>
                </div>
            </div>
            
            <div class="about-stats">
                <div class="about-stat">
                    <h4>10,000+</h4>
                    <p>Cars Sold</p>
                </div>
                <div class="about-stat">
                    <h4>5,000+</h4>
                    <p>Happy Customers</p>
                </div>
                <div class="about-stat">
                    <h4>50+</h4>
                    <p>Cities</p>
                </div>
                <div class="about-stat">
                    <h4>24/7</h4>
                    <p>Support</p>
                </div>
            </div>
        </section>
        
        <section class="testimonials-section">
            <h2 class="section-title">What Our Customers Say</h2>
            
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>CarBazaar made selling my car so easy! Got a great price and the process was completely hassle-free.</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial1.jpg" alt="Rahul Sharma">
                        <div>
                            <h4>Rahul Sharma</h4>
                            <p>Mumbai</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>Found my dream car at a great price. The verification process gave me confidence in my purchase.</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial2.jpg" alt="Priya Patel">
                        <div>
                            <h4>Priya Patel</h4>
                            <p>Delhi</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>Excellent platform for first-time car buyers. The customer support team was very helpful.</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial3.jpg" alt="Amit Kumar">
                        <div>
                            <h4>Amit Kumar</h4>
                            <p>Bangalore</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
