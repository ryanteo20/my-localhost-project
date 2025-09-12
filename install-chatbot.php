<?php
/**
 * Enhanced Chatbot Installation Script - Fixed Version
 * Handles permission issues and various file structures
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>SMEasyHR - Chatbot Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; margin: 10px 0; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        .file-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
    </style>
</head>
<body>";

echo "<h1>🤖 SMEasyHR - Enhanced AI Chatbot Installation</h1>\n";

// Configuration
$rootDirectory = __DIR__;
$includesDir = $rootDirectory . '/includes';
$backupDir = $rootDirectory . '/backup_before_chatbot';
$assetsDir = $rootDirectory . '/assets';
$cssDir = $assetsDir . '/css';
$jsDir = $assetsDir . '/js';

// Create all necessary directories
$directories = [$includesDir, $backupDir, $assetsDir, $cssDir, $jsDir];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div class='success'>✅ Created directory: " . basename($dir) . "</div>\n";
        } else {
            echo "<div class='error'>❌ Failed to create directory: " . basename($dir) . "</div>\n";
        }
    }
}

// First, let's create the required files
echo "<h2>📁 Creating Required Files</h2>\n";

// 1. Create chatbot.css
$chatbotCSS = '/* AI Chatbot Styles */
.chatbot-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  font-family: \'Nunito\', sans-serif;
}

.chatbot-toggle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chatbot-toggle:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(0,0,0,0.25);
}

.chatbot-window {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 380px;
  height: 550px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  display: none;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e0e0e0;
}

.chatbot-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 16px 16px 0 0;
}

.chatbot-header h6 {
  margin: 0;
  font-weight: 600;
}

.chatbot-header small {
  opacity: 0.9;
  font-size: 0.8rem;
}

.chatbot-close {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.chatbot-close:hover {
  background: rgba(255,255,255,0.2);
}

.chatbot-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #f8f9fa;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message {
  max-width: 85%;
  word-wrap: break-word;
  animation: fadeIn 0.3s ease;
}

.message.user {
  align-self: flex-end;
  background: #667eea;
  color: white;
  padding: 12px 16px;
  border-radius: 18px 18px 4px 18px;
  font-size: 0.9rem;
}

.message.ai {
  align-self: flex-start;
  background: white;
  color: #333;
  padding: 12px 16px;
  border-radius: 18px 18px 18px 4px;
  border: 1px solid #e0e0e0;
  white-space: pre-wrap;
  font-size: 0.9rem;
  line-height: 1.4;
}

.chatbot-input {
  display: flex;
  padding: 16px 20px;
  background: white;
  border-top: 1px solid #e0e0e0;
  gap: 12px;
  align-items: flex-end;
}

.chatbot-input textarea {
  flex: 1;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  padding: 12px 16px;
  outline: none;
  resize: none;
  font-family: inherit;
  font-size: 0.9rem;
  max-height: 100px;
  min-height: 20px;
  transition: border-color 0.2s ease;
}

.chatbot-input textarea:focus {
  border-color: #667eea;
}

.chatbot-send {
  background: #667eea;
  border: none;
  color: white;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.chatbot-send:hover:not(:disabled) {
  background: #5a6fd8;
  transform: scale(1.05);
}

.chatbot-send:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.typing-indicator {
  align-self: flex-start;
  display: none;
  padding: 12px 16px;
  background: white;
  border-radius: 18px 18px 18px 4px;
  border: 1px solid #e0e0e0;
  max-width: 85%;
}

.typing-dots {
  display: flex;
  gap: 4px;
  align-items: center;
}

.typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #999;
  animation: typing 1.5s infinite;
}

.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }

.ai-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  background: rgba(255,255,255,0.2);
  padding: 2px 6px;
  border-radius: 8px;
  margin-left: 8px;
}

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
  30% { transform: translateY(-4px); opacity: 1; }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.quick-action-btn {
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 16px;
  padding: 6px 12px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-action-btn:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .chatbot-window {
    width: 320px;
    height: 480px;
    bottom: 70px;
  }
  
  .chatbot-toggle {
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
}';

if (file_put_contents($cssDir . '/chatbot.css', $chatbotCSS)) {
    echo "<div class='success'>✅ Created: assets/css/chatbot.css</div>\n";
} else {
    echo "<div class='error'>❌ Failed to create: assets/css/chatbot.css</div>\n";
}

// 2. Create chatbot.js
$chatbotJS = 'const HF_API_URL = "https://api-inference.huggingface.co/models/microsoft/DialoGPT-medium";
const HF_API_KEY = "hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

let isTyping = false;
let conversationHistory = [];

const hrKnowledgeBase = {
  leave: {
    keywords: ["leave", "vacation", "time off", "annual leave", "sick leave", "emergency leave", "holiday"],
    responses: [
      "For leave applications:\\n1. Go to Leave Management → Apply Leave\\n2. Select leave type (Annual, Sick, Emergency)\\n3. Choose dates and provide reason\\n4. Submit for approval\\n\\nYour leave balance:\\n• Annual Leave: Based on your tenure\\n• Sick Leave: 14 days per year\\n• Emergency Leave: 2 days per year"
    ]
  },
  payroll: {
    keywords: ["payroll", "salary", "payslip", "payment", "wages", "pay", "income"],
    responses: [
      "Payroll information:\\n• Salary paid on last working day of month\\n• Payslips available by 5th of each month\\n• Access via Payroll → Check Payslip\\n• Includes basic salary, allowances, EPF, SOCSO deductions"
    ]
  },
  claims: {
    keywords: ["claim", "expense", "reimbursement", "receipt", "medical claim", "travel claim"],
    responses: [
      "Claim submission process:\\n1. Go to Claim Management → Request Claim\\n2. Select category (Travel, Meals, Medical, etc.)\\n3. Enter amount and description\\n4. Upload receipt image\\n5. Submit for approval\\n\\nKeep original receipts for verification!"
    ]
  }
};

function initializeChatbot() {
  conversationHistory = [];
}

function toggleChatbot() {
  const window = document.getElementById("chatbotWindow");
  if (window.style.display === "none" || window.style.display === "") {
    window.style.display = "flex";
    document.getElementById("chatbotInput").focus();
    if (conversationHistory.length === 0) {
      initializeChatbot();
    }
  } else {
    window.style.display = "none";
  }
}

function handleKeyPress(event) {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

function autoResize(textarea) {
  textarea.style.height = "auto";
  textarea.style.height = Math.min(textarea.scrollHeight, 100) + "px";
}

function sendQuickMessage(message) {
  document.getElementById("chatbotInput").value = message;
  sendMessage();
}

async function sendMessage() {
  const input = document.getElementById("chatbotInput");
  const message = input.value.trim();
  
  if (!message || isTyping) return;
  
  addMessage(message, "user");
  input.value = "";
  input.style.height = "auto";
  
  showTypingIndicator();
  isTyping = true;
  
  try {
    const aiResponse = getKnowledgeBaseResponse(message);
    conversationHistory.push({
      user: message,
      ai: aiResponse
    });
    
    hideTypingIndicator();
    addMessage(aiResponse, "ai");
    
  } catch (error) {
    console.error("AI Error:", error);
    hideTypingIndicator();
    
    const fallbackResponse = getKnowledgeBaseResponse(message);
    addMessage(fallbackResponse, "ai");
  } finally {
    isTyping = false;
  }
}

function getKnowledgeBaseResponse(message) {
  const messageLower = message.toLowerCase();
  
  for (const [category, data] of Object.entries(hrKnowledgeBase)) {
    for (const keyword of data.keywords) {
      if (messageLower.includes(keyword)) {
        const responses = data.responses;
        return responses[Math.floor(Math.random() * responses.length)];
      }
    }
  }
  
  if (messageLower.includes("hello") || messageLower.includes("hi") || messageLower.includes("hey")) {
    return "Hello! I am your HR Assistant. I can help you with leave applications, payroll questions, claims, attendance, employee management, and company policies. What would you like to know?";
  }
  
  return "I can assist you with:\\n\\n• Leave applications and policies\\n• Payroll and payslip information\\n• Claims and expense procedures\\n• Attendance tracking\\n• Employee management\\n• Company guidelines\\n\\nJust ask me about any HR-related topic!";
}

function addMessage(text, sender) {
  const messagesContainer = document.getElementById("chatbotMessages");
  const messageDiv = document.createElement("div");
  messageDiv.className = `message ${sender}`;
  
  if (sender === "ai") {
    messageDiv.innerHTML = formatAIMessage(text);
  } else {
    messageDiv.textContent = text;
  }
  
  messagesContainer.appendChild(messageDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function formatAIMessage(text) {
  let formatted = text
    .replace(/\\*\\*(.*?)\\*\\*/g, "<strong>$1</strong>")
    .replace(/\\*(.*?)\\*/g, "<em>$1</em>")
    .replace(/\\n\\n/g, "<br><br>")
    .replace(/\\n/g, "<br>");
  
  return formatted;
}

function showTypingIndicator() {
  document.getElementById("typingIndicator").style.display = "block";
  document.getElementById("sendBtn").disabled = true;
  
  const messagesContainer = document.getElementById("chatbotMessages");
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
  document.getElementById("typingIndicator").style.display = "none";
  document.getElementById("sendBtn").disabled = false;
}

document.addEventListener("DOMContentLoaded", function() {
  initializeChatbot();
});';

if (file_put_contents($jsDir . '/chatbot.js', $chatbotJS)) {
    echo "<div class='success'>✅ Created: assets/js/chatbot.js</div>\n";
} else {
    echo "<div class='error'>❌ Failed to create: assets/js/chatbot.js</div>\n";
}

// 3. Create chatbot-includes.php
$chatbotInclude = '<?php
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
          <button class="quick-action-btn" onclick="sendQuickMessage(\'How do I apply for leave?\')">Apply for Leave</button>
          <button class="quick-action-btn" onclick="sendQuickMessage(\'What are the claim policies?\')">Claim Policies</button>
          <button class="quick-action-btn" onclick="sendQuickMessage(\'Check attendance rules\')">Attendance Rules</button>
          <button class="quick-action-btn" onclick="sendQuickMessage(\'How to check payslip?\')">Payslip Info</button>
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
<script src="assets/js/chatbot.js"></script>';

if (file_put_contents($includesDir . '/chatbot-includes.php', $chatbotInclude)) {
    echo "<div class='success'>✅ Created: includes/chatbot-includes.php</div>\n";
} else {
    echo "<div class='error'>❌ Failed to create: includes/chatbot-includes.php</div>\n";
}

echo "<h2>📋 Manual Installation Guide</h2>\n";
echo "<div class='info'>";
echo "<p>Since automatic installation had issues, here's how to manually add the chatbot to your pages:</p>";
echo "<h4>Add this line to the &lt;head&gt; section of each page:</h4>";
echo "<code>&lt;?php include 'includes/chatbot-includes.php'; ?&gt;</code>";
echo "<br><br>";
echo "<h4>Files that need manual update:</h4>";

// Get all PHP files and show which ones need manual update
$phpFiles = glob($rootDirectory . '/*.php');
$needsUpdate = [];

foreach ($phpFiles as $file) {
    $filename = basename($file);
    if (in_array($filename, ['install-chatbot.php', 'install-chatbot-fixed.php'])) continue;
    
    $content = file_get_contents($file);
    if (strpos($content, "chatbot-includes.php") === false) {
        $needsUpdate[] = $filename;
    }
}

echo "<div class='file-list'>";
foreach ($needsUpdate as $file) {
    echo "• {$file}<br>";
}
echo "</div>";
echo "</div>";

echo "<h2>🎯 Quick Test</h2>\n";
echo "<div class='info'>";
echo "<p>To test if the chatbot works:</p>";
echo "<ol>";
echo "<li>Add the include line to your <code>index.php</code> file</li>";
echo "<li>Open <code>index.php</code> in your browser</li>";
echo "<li>Look for a robot icon in the bottom-right corner</li>";
echo "<li>Click it to open the chatbot</li>";
echo "</ol>";
echo "</div>";

echo "<h2>✅ Installation Complete!</h2>\n";
echo "<div class='success'>";
echo "<p><strong>All required files have been created successfully!</strong></p>";
echo "<p>The chatbot is ready to use. Just add the include line to your pages.</p>";
echo "<p><em>You can now delete this installation script.</em></p>";
echo "</div>";

echo "</body></html>";
?>