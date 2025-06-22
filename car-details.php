<?php
session_start();
require 'db-config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Get car details
$stmt = $conn->prepare("SELECT c.*, u.username AS seller_name, u.phone AS seller_phone, 
                        u.email AS seller_email, u.profile_image AS seller_image, 
                        u.location AS seller_location, u.is_verified AS seller_verified
                        FROM cars c
                        JOIN users u ON c.seller_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$car = $result->fetch_assoc();
$stmt->close();

// Get car images
$car_images = [$car['image_path']];
if (!empty($car['image_path2'])) $car_images[] = $car['image_path2'];
if (!empty($car['image_path3'])) $car_images[] = $car['image_path3'];

// Check if car is in favorites
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $car_id);
    $stmt->execute();
    $is_favorite = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please login to send a message.";
        header("Location: login.php");
        exit();
    }
    
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $car['seller_id'];
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, car_id, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $sender_id, $receiver_id, $car_id, $message);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Message sent successfully!";
        header("Location: car-details.php?id=$car_id");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send message. Please try again.";
        header("Location: car-details.php?id=$car_id");
        exit();
    }
}

// Handle mark as sold
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_sold'])) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $car['seller_id'] && $_SESSION['user_type'] != 'admin')) {
        $_SESSION['error'] = "You don't have permission to perform this action.";
        header("Location: car-details.php?id=$car_id");
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE cars SET is_sold = TRUE WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car marked as sold!";
        header("Location: car-details.php?id=$car_id");
        exit();
    } else {
        $_SESSION['error'] = "Failed to mark car as sold. Please try again.";
        header("Location: car-details.php?id=$car_id");
        exit();
    }
}

// Handle delete car
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_car'])) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $car['seller_id'] && $_SESSION['user_type'] != 'admin')) {
        $_SESSION['error'] = "You don't have permission to perform this action.";
        header("Location: car-details.php?id=$car_id");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car deleted successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete car. Please try again.";
        header("Location: car-details.php?id=$car_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
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
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="car-details-container">
            <!-- Car Gallery -->
            <div class="car-gallery">
                <div class="car-carousel">
                    <?php foreach ($car_images as $image): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" class="car-main-image">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Car Info -->
            <div class="car-info">
                <h1 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h1>
                <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                
                <div class="car-meta">
                    <div class="car-meta-item">
                        <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?>
                    </div>
                    <div class="car-meta-item">
                        <i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km
                    </div>
                    <div class="car-meta-item">
                        <i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?>
                    </div>
                    <div class="car-meta-item">
                        <i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?>
                    </div>
                    <div class="car-meta-item">
                        <i class="fas fa-user"></i> <?php echo $car['is_sold'] ? 'Sold' : 'Available'; ?>
                    </div>
                </div>
                
                <div class="car-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                </div>
                
                <div class="car-actions">
                    <form method="POST" action="favorites.php" style="display: inline;">
                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        <button type="submit" name="toggle_favorite" class="favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>">
                            <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Remove Favorite' : 'Add to Favorites'; ?>
                        </button>
                    </form>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $car['seller_id']): ?>
                        <button class="btn btn-primary" onclick="document.getElementById('message-form').scrollIntoView()">
                            <i class="fas fa-comment"></i> Contact Seller
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $car['seller_id'] || $_SESSION['user_type'] == 'admin')): ?>
                        <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <button type="submit" name="delete_car" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this car?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        <?php if (!$car['is_sold']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                <button type="submit" name="mark_sold" class="btn btn-success">
                                    <i class="fas fa-check-circle"></i> Mark as Sold
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Seller Card -->
        <div class="seller-card">
            <div class="seller-header">
                <div class="seller-avatar">
                    <img src="<?php echo !empty($car['seller_image']) ? htmlspecialchars($car['seller_image']) : 'images/default-avatar.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($car['seller_name']); ?>">
                    <?php if ($car['seller_verified']): ?>
                        <div class="seller-verified">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="seller-info">
                    <h3><?php echo htmlspecialchars($car['seller_name']); ?></h3>
                    <?php if (!empty($car['seller_location'])): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($car['seller_location']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($car['seller_phone'])): ?>
                        <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($car['seller_phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="seller-stats">
                <div class="seller-stat">
                    <h4><?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM cars WHERE seller_id = ?");
                        $stmt->bind_param("i", $car['seller_id']);
                        $stmt->execute();
                        $stmt->bind_result($total_cars);
                        $stmt->fetch();
                        $stmt->close();
                        echo $total_cars;
                    ?></h4>
                    <p>Cars Listed</p>
                </div>
                <div class="seller-stat">
                    <h4><?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM cars WHERE seller_id = ? AND is_sold = TRUE");
                        $stmt->bind_param("i", $car['seller_id']);
                        $stmt->execute();
                        $stmt->bind_result($sold_cars);
                        $stmt->fetch();
                        $stmt->close();
                        echo $sold_cars;
                    ?></h4>
                    <p>Cars Sold</p>
                </div>
                <div class="seller-stat">
                    <h4><?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ?");
                        $stmt->bind_param("i", $car['seller_id']);
                        $stmt->execute();
                        $stmt->bind_result($total_messages);
                        $stmt->fetch();
                        $stmt->close();
                        echo $total_messages;
                    ?></h4>
                    <p>Messages</p>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $car['seller_id']): ?>
                <div id="message-form">
                    <h3>Contact Seller</h3>
                    <form method="POST">
                        <div class="form-group">
                            <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            $('.car-carousel').slick({
                dots: true,
                infinite: true,
                speed: 300,
                slidesToShow: 1,
                adaptiveHeight: true
            });
        });
    </script>
</body>
</html>
