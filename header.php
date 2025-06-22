<!-- Header -->
<header>
    <div class="container header-container">
        <a href="index.php" class="logo">
            <div class="logo-icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="logo-text">Car<span>Bazaar</span></div>
        </a>
        
        <nav>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="contact.php"><i class="fas fa-phone-alt"></i> Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                    <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="user-actions">
            <?php if (isset($_SESSION['username'])): ?>
                <div class="user-greeting">
                    Welcome, 
                    <span>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <?php if ($_SESSION['is_verified']): ?>
                            <i class="fas fa-check-circle verified-icon"></i>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline dropdown-toggle" type="button" id="userMenu" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> My Account
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <?php if ($_SESSION['user_type'] == 'seller'): ?>
                            <a class="dropdown-item" href="verify-seller.php">
                                <i class="fas fa-id-card"></i> Verification
                                <?php 
                                // Check for pending verification requests
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_requests 
                                                       WHERE user_id = ? AND status = 'pending'");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $stmt->bind_result($pending_requests);
                                $stmt->fetch();
                                $stmt->close();
                                
                                if ($pending_requests > 0): ?>
                                    <span class="badge badge-pill badge-danger">!</span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
                            <a class="dropdown-item" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                <?php 
                                // Check for pending verification requests
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_requests 
                                                       WHERE status = 'pending'");
                                $stmt->execute();
                                $stmt->bind_result($pending_requests);
                                $stmt->fetch();
                                $stmt->close();
                                
                                if ($pending_requests > 0): ?>
                                    <span class="badge badge-pill badge-danger"><?php echo $pending_requests; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="?logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
