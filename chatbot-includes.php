<?php
/**
 * Enhanced Chatbot includes - More User Friendly Design
 */
?>

<!-- Chatbot CSS -->
<link href="assets/css/chatbot.css" rel="stylesheet">

<!-- AI Chatbot Widget HTML -->
<div class="chatbot-container">
  <button class="chatbot-toggle" onclick="toggleChatbot()" title="Chat with HR Assistant">
    <i class="bi bi-robot"></i>
  </button>
  
  <div class="chatbot-window" id="chatbotWindow" style="display: none;">
    <div class="chatbot-header">
      <div class="chatbot-header-info">
        <h6>
          <i class="bi bi-robot"></i>
          HR Assistant 
          <span class="ai-badge">
            <i class="bi bi-lightning-fill"></i>
            AI-Powered
          </span>
        </h6>
        <small>Your friendly HR helper • Always here to assist</small>
      </div>
      <button class="chatbot-close" onclick="toggleChatbot()" title="Close chat">
        <i class="bi bi-x"></i>
      </button>
    </div>
    
    <div class="chatbot-messages" id="chatbotMessages">
      <!-- Messages will be loaded here dynamically -->
    </div>
    
    <div class="typing-indicator" id="typingIndicator" style="display: none;">
      <!-- Typing indicator will be inserted here -->
    </div>
    
    <div class="chatbot-input">
      <textarea 
        id="chatbotInput" 
        placeholder="Type your message here... 💬" 
        rows="1" 
        onkeypress="handleKeyPress(event)" 
        oninput="autoResize(this)"
        autocomplete="off"
        spellcheck="true">
      </textarea>
      <button class="chatbot-send" id="sendBtn" onclick="sendMessage()" title="Send message">
        <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</div>

<!-- Chatbot JavaScript -->
<script src="assets/js/chatbot.js"></script>

<!-- Initialize Chatbot -->
<script>
// Fallback initialization in case the main script doesn't load
document.addEventListener('DOMContentLoaded', function() {
  console.log('Chatbot initialization check...');
  
  // Check if main functions exist
  if (typeof toggleChatbot !== 'function') {
    console.error('Chatbot functions not loaded. Check assets/js/chatbot.js');
    
    // Basic fallback function
    window.toggleChatbot = function() {
      const window = document.getElementById('chatbotWindow');
      if (window) {
        if (window.style.display === 'none' || window.style.display === '') {
          window.style.display = 'flex';
          console.log('Chatbot opened');
        } else {
          window.style.display = 'none';
          console.log('Chatbot closed');
        }
      } else {
        console.error('Chatbot window element not found');
      }
    };
  }
});
</script>