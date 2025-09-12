<?php
/**
 * Chatbot includes - Add this to all pages
 * This file contains the CSS and HTML for the AI chatbot
 */
?>

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
        <h6>AI HR Assistant <span class="ai-badge"><i class="bi bi-cpu"></i> HF-AI</span></h6>
        <small>Ask me anything about HR!</small>
      </div>
      <button class="chatbot-close" onclick="toggleChatbot()">
        <i class="bi bi-x"></i>
      </button>
    </div>
    
    <div class="chatbot-messages" id="chatbotMessages">
      <div class="message ai">
        🤖 Hello! I am your AI-powered HR Assistant.
        
        I can help you with:
        • Leave policies and applications
        • Payroll and compensation questions
        • Claims and expense procedures
        • Attendance policies
        • Employee management
        • Company guidelines
        
        What would you like to know?
        
        <div class="quick-actions">
          <button class="quick-action-btn" onclick="sendQuickMessage('How do I apply for leave?')">Apply for Leave</button>
          <button class="quick-action-btn" onclick="sendQuickMessage('What are the claim policies?')">Claim Policies</button>
          <button class="quick-action-btn" onclick="sendQuickMessage('Check attendance rules')">Attendance Rules</button>
          <button class="quick-action-btn" onclick="sendQuickMessage('How to check payslip?')">Payslip Info</button>
        </div>
      </div>
      
      <div class="typing-indicator" id="typingIndicator">
        <div class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
    
    <div class="chatbot-input">
      <textarea id="chatbotInput" placeholder="Type your HR question..." rows="1" onkeypress="handleKeyPress(event)" oninput="autoResize(this)"></textarea>
      <button class="chatbot-send" id="sendBtn" onclick="sendMessage()">
        <i class="bi bi-send"></i>
      </button>
    </div>
  </div>
</div>

<!-- Chatbot JavaScript -->
<script src="assets/js/chatbot.js"></script>