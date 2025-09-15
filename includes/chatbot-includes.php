<!-- Chatbot CSS -->
<link href="assets/css/chatbot.css" rel="stylesheet">

<!-- AI Chatbot Widget HTML -->
<div class="chatbot-container">
  <button class="chatbot-toggle" onclick="toggleChatbot()">
    <i class="bi bi-robot"></i>
  </button>
  
  <div class="chatbot-window" id="chatbotWindow">
    <div class="chatbot-header">
      <div>
        <h6>AI Assistant <span class="ai-badge"><i class="bi bi-cpu"></i> Gemini AI</span></h6>
        <small>Powered by Google AI - Ask me anything!</small>
      </div>
      <button class="chatbot-close" onclick="toggleChatbot()">
        <i class="bi bi-x"></i>
      </button>
    </div>
    
    <div class="chatbot-messages" id="chatbotMessages">
      <!-- Initial message will be loaded by JavaScript -->
      
      <div class="typing-indicator" id="typingIndicator">
        <div class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
    
    <div class="chatbot-input">
      <textarea id="chatbotInput" placeholder="Ask me anything..." rows="1" onkeypress="handleKeyPress(event)" oninput="autoResize(this)"></textarea>
      <button class="chatbot-send" id="sendBtn" onclick="sendMessage()">
        <i class="bi bi-send"></i>
      </button>
    </div>
  </div>
</div>

<!-- Chatbot JavaScript -->
<script src="assets/js/chatbot.js"></script>