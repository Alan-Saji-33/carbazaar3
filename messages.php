<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get conversations
$conversations = [];
$stmt = $conn->prepare("SELECT 
    m.id AS conversation_id,
    u.id AS user_id,
    u.username,
    u.profile_image,
    u.is_verified,
    m.message,
    m.created_at,
    m.is_read,
    (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = FALSE) AS unread_count
FROM messages m
JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
WHERE (m.sender_id = ? OR m.receiver_id = ?)
GROUP BY u.id
ORDER BY m.created_at DESC");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$conversations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get selected conversation messages
$selected_conversation = null;
$messages = [];
if (isset($_GET['conversation'])) {
    $conversation_id = filter_input(INPUT_GET, 'conversation', FILTER_SANITIZE_NUMBER_INT);
    
    // Get conversation details
    $stmt = $conn->prepare("SELECT 
        u.id AS user_id,
        u.username,
        u.profile_image,
        u.is_verified
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
    WHERE m.id = ?");
    $stmt->bind_param("ii", $user_id, $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_conversation = $result->fetch_assoc();
    $stmt->close();
    
    if ($selected_conversation) {
        // Get messages for this conversation
        $stmt = $conn->prepare("SELECT 
            m.*,
            u.username AS sender_name,
            u.profile_image AS sender_image
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC");
        $stmt->bind_param("iiii", $user_id, $selected_conversation['user_id'], $selected_conversation['user_id'], $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Mark messages as read
        $conn->query("UPDATE messages SET is_read = TRUE WHERE receiver_id = $user_id AND sender_id = {$selected_conversation['user_id']}");
    }
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_SANITIZE_NUMBER_INT);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        header("Location: messages.php?conversation=" . $conn->insert_id);
        exit();
    } else {
        $_SESSION['error'] = "Failed to send message. Please try again.";
        header("Location: messages.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <div class="messages-container">
            <!-- Conversations Sidebar -->
            <div class="messages-sidebar">
                <h3>Conversations</h3>
                
                <ul class="conversation-list">
                    <?php if (count($conversations) > 0): ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <li class="conversation-item <?php echo isset($selected_conversation) && $selected_conversation['user_id'] == $conversation['user_id'] ? 'active' : ''; ?>" 
                                data-conversation-id="<?php echo $conversation['conversation_id']; ?>">
                                <div class="conversation-header">
                                    <img src="<?php echo !empty($conversation['profile_image']) ? htmlspecialchars($conversation['profile_image']) : 'images/default-avatar.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($conversation['username']); ?>" class="conversation-avatar">
                                    <div class="conversation-name">
                                        <?php echo htmlspecialchars($conversation['username']); ?>
                                        <?php if ($conversation['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-icon"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-time">
                                        <?php echo date('h:i A', strtotime($conversation['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="conversation-preview">
                                    <?php echo htmlspecialchars(substr($conversation['message'], 0, 50)); ?>...
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="badge badge-pill badge-danger"><?php echo $conversation['unread_count']; ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-center" style="padding: 20px; color: var(--gray);">
                            <i class="fas fa-comments" style="font-size: 24px; margin-bottom: 10px;"></i>
                            <p>No conversations yet</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Messages Content -->
            <div class="messages-content">
                <?php if (isset($selected_conversation)): ?>
                    <div class="conversation-header">
                        <img src="<?php echo !empty($selected_conversation['profile_image']) ? htmlspecialchars($selected_conversation['profile_image']) : 'images/default-avatar.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($selected_conversation['username']); ?>" class="conversation-avatar">
                        <div class="conversation-name">
                            <?php echo htmlspecialchars($selected_conversation['username']); ?>
                            <?php if ($selected_conversation['is_verified']): ?>
                                <i class="fas fa-check-circle verified-icon"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="message-container">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $user_id ? 'message-sent' : 'message-received'; ?>">
                                <div class="message-content">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                </div>
                                <div class="message-info">
                                    <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                    <?php if ($message['sender_id'] == $user_id && $message['is_read']): ?>
                                        <i class="fas fa-check-double" style="color: var(--primary); margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" class="message-input">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_conversation['user_id']; ?>">
                        <textarea name="message" placeholder="Type your message here..." required></textarea>
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center" style="padding: 40px; color: var(--gray);">
                        <i class="fas fa-comments" style="font-size: 60px; margin-bottom: 20px;"></i>
                        <h3>Select a conversation</h3>
                        <p>Choose a conversation from the sidebar to view messages</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
