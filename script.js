// Toggle favorite button
function toggleFavorite(button, carId) {
    if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
        window.location.href = 'login.php';
        return;
    }
    
    button.classList.toggle('active');
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'favorites.php';
    form.style.display = 'none';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'car_id';
    input.value = carId;
    
    const submit = document.createElement('input');
    input.type = 'hidden';
    input.name = 'toggle_favorite';
    
    form.appendChild(input);
    form.appendChild(submit);
    document.body.appendChild(form);
    form.submit();
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Preview image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    }

    if (file) {
        reader.readAsDataURL(file);
    }
}

// Car gallery thumbnail click
document.querySelectorAll('.car-thumbnail').forEach(thumbnail => {
    thumbnail.addEventListener('click', function() {
        const mainImage = document.querySelector('.car-main-image');
        const thumbImage = this.querySelector('img');
        mainImage.src = thumbImage.src;
        
        document.querySelectorAll('.car-thumbnail').forEach(t => {
            t.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Real-time messaging
function loadMessages(conversationId) {
    // AJAX call to load messages for the conversation
    fetch(`get-messages.php?conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            const messageContainer = document.querySelector('.message-container');
            messageContainer.innerHTML = '';
            
            data.messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.sender_id == <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?> ? 'message-sent' : 'message-received'}`;
                
                messageDiv.innerHTML = `
                    <div class="message-content">${message.message}</div>
                    <div class="message-info">
                        ${new Date(message.created_at).toLocaleTimeString()}
                    </div>
                `;
                
                messageContainer.appendChild(messageDiv);
            });
            
            // Scroll to bottom
            messageContainer.scrollTop = messageContainer.scrollHeight;
        });
}

// Send message
document.querySelector('.message-send-btn').addEventListener('click', function() {
    const messageInput = document.querySelector('.message-input textarea');
    const message = messageInput.value.trim();
    
    if (message) {
        // AJAX call to send message
        fetch('send-message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadMessages(currentConversationId);
            }
        });
    }
});

// Initialize carousel
function initCarousel() {
    $('.car-carousel').slick({
        dots: true,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        adaptiveHeight: true
    });
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any carousels
    if (document.querySelector('.car-carousel')) {
        initCarousel();
    }
    
    // Set active conversation
    const conversationItems = document.querySelectorAll('.conversation-item');
    conversationItems.forEach(item => {
        item.addEventListener('click', function() {
            conversationItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            const conversationId = this.dataset.conversationId;
            loadMessages(conversationId);
        });
    });
    
    // Set first conversation as active if none is selected
    if (conversationItems.length > 0 && !document.querySelector('.conversation-item.active')) {
        conversationItems[0].classList.add('active');
        loadMessages(conversationItems[0].dataset.conversationId);
    }
});
