<?php
session_start();
require 'db-config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contact_submit'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // In a real application, you would send an email or save to database here
    $_SESSION['message'] = "Thank you for contacting us! We'll get back to you soon.";
    header("Location: contact.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <section class="contact-section">
            <div class="contact-container">
                <div class="contact-info">
                    <h2 class="section-title">Contact Information</h2>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Address</h3>
                            <p>123 Car Street, Mumbai, Maharashtra 400001</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h3>Phone</h3>
                            <p>+91 9876543210</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>info@carbazaar.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h2 class="section-title">Send Us a Message</h2>
                    
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your Name</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Your Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="contact_submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <section class="map-section">
            <h2 class="section-title">Our Location</h2>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3771.715671416601!2d72.83266031490066!3d19.03355878711344!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7ce6e593e1a8d%3A0x1b8e6a7a1a1a1a1a!2sCarBazaar!5e0!3m2!1sen!2sin!4v1620000000000!5m2!1sen!2sin" 
                        width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
