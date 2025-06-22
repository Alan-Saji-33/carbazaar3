<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db-config.php';

// Get cars for listing
$search_where = "WHERE c.is_sold = FALSE";
$search_params = [];
$param_types = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000000;
    $fuel_type = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';
    $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
    $location = isset($_GET['location']) ? $_GET['location'] : '';

    $search_where = "WHERE (c.model LIKE ? OR c.brand LIKE ? OR c.description LIKE ?) 
                    AND c.price BETWEEN ? AND ? AND c.is_sold = FALSE";
    $search_params = ["%$search%", "%$search%", "%$search%", $min_price, $max_price];
    $param_types = "sssii";

    if (!empty($fuel_type)) {
        $search_where .= " AND c.fuel_type = ?";
        $search_params[] = $fuel_type;
        $param_types .= "s";
    }

    if (!empty($transmission)) {
        $search_where .= " AND c.transmission = ?";
        $search_params[] = $transmission;
        $param_types .= "s";
    }

    if (!empty($location)) {
        $search_where .= " AND u.location LIKE ?";
        $search_params[] = "%$location%";
        $param_types .= "s";
    }
}

$sql = "SELECT c.*, u.username AS seller_name, u.phone AS seller_phone, 
        u.email AS seller_email, u.profile_image AS seller_image, 
        u.location AS seller_location, u.is_verified AS seller_verified
        FROM cars c
        JOIN users u ON c.seller_id = u.id
        $search_where
        ORDER BY c.created_at DESC
        LIMIT 12";

$stmt = $conn->prepare($sql);
if (!empty($search_params)) {
    $stmt->bind_param($param_types, ...$search_params);
}
$stmt->execute();
$cars_result = $stmt->get_result();

// Get favorites if user is logged in
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_stmt = $conn->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['car_id'];
    }
    $fav_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBazaar - Used Car Selling Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Used Car</h1>
            <p>Buy and sell quality used cars from trusted sellers across India</p>
            <div class="hero-buttons">
                <a href="#cars" class="btn btn-primary">
                    <i class="fas fa-car"></i> Browse Cars
                </a>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add-car.php" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Add Car
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <div class="container">
        <div class="search-section" id="search">
            <div class="search-title">
                <h2>Find Your Dream Car</h2>
                <p>Search through our extensive inventory of quality used cars</p>
            </div>
            
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Keywords</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Toyota, Honda, SUV..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           placeholder="City or State" 
                           value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_price"><i class="fas fa-rupee-sign"></i> Min Price</label>
                    <input type="number" id="min_price" name="min_price" class="form-control" 
                           min="0" placeholder="₹10,000" 
                           value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price"><i class="fas fa-rupee-sign"></i> Max Price</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" 
                           min="0" placeholder="₹50,00,000" 
                           value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '10000000'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="">Any Fuel Type</option>
                        <option value="Petrol" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Electric') ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="CNG" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'CNG') ? 'selected' : ''; ?>>CNG</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="">Any Transmission</option>
                        <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search Cars
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

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

        <!-- Cars Section -->
        <section id="cars">
            <div class="section-header">
                <h2 class="section-title">Available Cars</h2>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add-car.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Car
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="cars-grid">
                <?php if ($cars_result->num_rows > 0): ?>
                    <?php while ($car = $cars_result->fetch_assoc()): ?>
                        <div class="car-card">
                            <?php if ($car['is_sold']): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php else: ?>
                                <div class="car-badge">NEW</div>
                            <?php endif; ?>
                            
                            <div class="car-image">
                                <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                            </div>
                            
                            <div class="car-details">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                                
                                <div class="car-specs">
                                    <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                    <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km</span>
                                    <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                    <span class="car-spec"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                </div>
                                
                                <div class="seller-info">
                                    <div class="seller-avatar">
                                        <img src="<?php echo !empty($car['seller_image']) ? htmlspecialchars($car['seller_image']) : 'images/default-avatar.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($car['seller_name']); ?>">
                                        <?php if ($car['seller_verified']): ?>
                                            <span class="verified-badge"><i class="fas fa-check-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="seller-details">
                                        <h4><?php echo htmlspecialchars($car['seller_name']); ?></h4>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($car['seller_location']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="car-actions">
                                    <form method="POST" action="favorites.php" style="display: inline;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="toggle_favorite" class="favorite-btn <?php echo in_array($car['id'], $favorites) ? 'active' : ''; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-car" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray);">No cars found matching your criteria</h3>
                        <p>Try adjusting your search filters or check back later for new listings</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-sync-alt"></i> Reset Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
