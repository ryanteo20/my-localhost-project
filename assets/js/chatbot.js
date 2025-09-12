/**
 * AI HR Chatbot using Hugging Face API
 * Reusable chatbot for SMEasyHR system
 */

// Configuration - Hugging Face Free API
const HF_API_URL = 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-medium';
const HF_API_KEY = 'hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Optional: Get free key from huggingface.co

// Alternative models you can use:
// const HF_API_URL = 'https://api-inference.huggingface.co/models/facebook/blenderbot-400M-distill';
// const HF_API_URL = 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-large';

// Chatbot state
let isTyping = false;
let conversationHistory = [];

// HR Knowledge Base for fallback responses
const hrKnowledgeBase = {
  leave: {
    keywords: ['leave', 'vacation', 'time off', 'annual leave', 'sick leave', 'emergency leave', 'holiday'],
    responses: [
      "For leave applications:\n1. Go to Leave Management → Apply Leave\n2. Select leave type (Annual, Sick, Emergency)\n3. Choose dates and provide reason\n4. Submit for approval\n\nYour leave balance:\n• Annual Leave: Based on your tenure\n• Sick Leave: 14 days per year\n• Emergency Leave: 2 days per year",
      "Leave policies:\n• Submit applications at least 3 days in advance\n• Medical certificate required for sick leave >1 day\n• Emergency leave requires proper documentation\n• Annual leave carries forward up to 10 days\n• Public holidays are separate from your leave entitlement"
    ]
  },
  payroll: {
    keywords: ['payroll', 'salary', 'payslip', 'payment', 'wages', 'pay', 'income'],
    responses: [
      "Payroll information:\n• Salary paid on last working day of month\n• Payslips available by 5th of each month\n• Access via Payroll → Check Payslip\n• Includes basic salary, allowances, EPF, SOCSO deductions",
      "To check your payslip:\n1. Navigate to Payroll section\n2. Click 'Check Payslip'\n3. Select the month\n4. Download or print your payslip\n\nFor payroll processing (Employers):\n1. Go to Payroll → Process Payroll\n2. Select employees and month\n3. Review calculations\n4. Generate payslips"
    ]
  },
  claims: {
    keywords: ['claim', 'expense', 'reimbursement', 'receipt', 'medical claim', 'travel claim'],
    responses: [
      "Claim submission process:\n1. Go to Claim Management → Request Claim\n2. Select category (Travel, Meals, Medical, etc.)\n3. Enter amount and description\n4. Upload receipt image\n5. Submit for approval\n\nKeep original receipts for verification!",
      "Claim policies:\n• Submit within 30 days of expense\n• Original receipts required\n• Medical claims up to RM500/year\n• Travel claims require prior approval\n• Processing time: 3-5 working days\n\nFor claim approval (Employers):\n• Go to Claim Management → Approve/Reject Claim\n• Review submitted claims and receipts\n• Approve or reject with comments"
    ]
  },
  attendance: {
    keywords: ['attendance', 'clock in', 'clock out', 'working hours', 'late', 'punctuality'],
    responses: [
      "Attendance information:\n• Working hours: 9:00 AM - 6:00 PM\n• Clock in/out via Attendance section\n• Grace period: 15 minutes\n• Lunch break: 1 hour\n• Late arrival requires explanation",
      "To clock in/out:\n1. Go to Attendance section\n2. Click 'Clock In/Out'\n3. System records your time automatically\n4. View your attendance history anytime\n\nFor viewing all attendance (Employers):\n• Go to Attendance → View All Employee Attendance\n• Monitor team attendance patterns\n• Generate attendance reports"
    ]
  },
  employee: {
    keywords: ['employee', 'staff', 'add employee', 'delete employee', 'view employee', 'management'],
    responses: [
      "Employee Management (For Employers):\n1. Add Employee: Go to Employee Management → Add Employee\n2. Delete Employee: Go to Employee Management → Delete Employee\n3. View All: Go to Employee Management → View All Employee\n\nRequired information for new employees:\n• Personal details\n• Contact information\n• Job position and department\n• Login credentials",
      "Employee management features:\n• Comprehensive employee database\n• Role-based access control\n• Employee profile management\n• Department and position tracking\n• Secure login system for each employee"
    ]
  },
  recruitment: {
    keywords: ['recruitment', 'hiring', 'job application', 'interview', 'candidate'],
    responses: [
      "Recruitment Process:\n• Go to Recruitment Process section\n• Manage job postings and applications\n• Track candidate progress\n• Schedule interviews\n• Store candidate information\n\nThe system helps streamline your hiring process from application to onboarding."
    ]
  }
};

/**
 * Initialize chatbot
 */
function initializeChatbot() {
  conversationHistory = [];
}

/**
 * Toggle chatbot window
 */
function toggleChatbot() {
  const window = document.getElementById('chatbotWindow');
  if (window.style.display === 'none' || window.style.display === '') {
    window.style.display = 'flex';
    document.getElementById('chatbotInput').focus();
    if (conversationHistory.length === 0) {
      initializeChatbot();
    }
  } else {
    window.style.display = 'none';
  }
}

/**
 * Handle keyboard input
 */
function handleKeyPress(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

/**
 * Auto-resize textarea
 */
function autoResize(textarea) {
  textarea.style.height = 'auto';
  textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
}

/**
 * Send quick action message
 */
function sendQuickMessage(message) {
  document.getElementById('chatbotInput').value = message;
  sendMessage();
}

/**
 * Send message to AI
 */
async function sendMessage() {
  const input = document.getElementById('chatbotInput');
  const message = input.value.trim();
  
  if (!message || isTyping) return;
  
  // Add user message to chat
  addMessage(message, 'user');
  input.value = '';
  input.style.height = 'auto';
  
  // Show typing indicator
  showTypingIndicator();
  isTyping = true;
  
  try {
    // First try to get response from Hugging Face
    let aiResponse = await getHuggingFaceResponse(message);
    
    // If HF response is not satisfactory, use knowledge base
    if (!aiResponse || aiResponse.length < 10 || aiResponse.includes('error')) {
      aiResponse = getKnowledgeBaseResponse(message);
    }
    
    // Add conversation to history
    conversationHistory.push({
      user: message,
      ai: aiResponse
    });
    
    // Hide typing indicator and show response
    hideTypingIndicator();
    addMessage(aiResponse, 'ai');
    
  } catch (error) {
    console.error('AI Error:', error);
    hideTypingIndicator();
    
    // Fallback to knowledge base
    const fallbackResponse = getKnowledgeBaseResponse(message);
    addMessage(fallbackResponse, 'ai');
  } finally {
    isTyping = false;
  }
}

/**
 * Get response from Hugging Face API
 */
async function getHuggingFaceResponse(message) {
  try {
    const response = await fetch(HF_API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(HF_API_KEY && HF_API_KEY !== 'hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' && {
          'Authorization': `Bearer ${HF_API_KEY}`
        })
      },
      body: JSON.stringify({
        inputs: `Human: ${message}\nAI Assistant: I'm an HR assistant. Let me help you with that.`,
        parameters: {
          max_length: 200,
          temperature: 0.7,
          do_sample: true,
          top_p: 0.9
        }
      })
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
      throw new Error(data.error);
    }
    
    // Extract the response text
    let aiResponse = '';
    if (Array.isArray(data) && data[0] && data[0].generated_text) {
      aiResponse = data[0].generated_text;
      // Clean up the response
      aiResponse = aiResponse.replace(/Human:.*?AI Assistant:/g, '').trim();
    }
    
    return aiResponse || getKnowledgeBaseResponse(message);
    
  } catch (error) {
    console.error('Hugging Face API Error:', error);
    return getKnowledgeBaseResponse(message);
  }
}

/**
 * Get response from local knowledge base
 */
function getKnowledgeBaseResponse(message) {
  const messageLower = message.toLowerCase();
  
  // Check each category in knowledge base
  for (const [category, data] of Object.entries(hrKnowledgeBase)) {
    for (const keyword of data.keywords) {
      if (messageLower.includes(keyword)) {
        const responses = data.responses;
        return responses[Math.floor(Math.random() * responses.length)];
      }
    }
  }
  
  // Default responses for common greetings
  if (messageLower.includes('hello') || messageLower.includes('hi') || messageLower.includes('hey')) {
    return "Hello! I'm your HR Assistant. I can help you with leave applications, payroll questions, claims, attendance, employee management, and company policies. What would you like to know?";
  }
  
  if (messageLower.includes('help') || messageLower.includes('what can you do')) {
    return "I can assist you with:\n\n• Leave applications and policies\n• Payroll and payslip information\n• Claims and expense procedures\n• Attendance tracking\n• Employee management\n• Recruitment processes\n• Company guidelines\n\nJust ask me about any HR-related topic!";
  }
  
  if (messageLower.includes('thank')) {
    return "You're welcome! Is there anything else I can help you with regarding HR matters?";
  }
  
  // Generic fallback
  return `I understand you're asking about "${message}". 

As your HR Assistant, I can help with:
• Leave management and applications
• Payroll and salary information  
• Claims and expense procedures
• Attendance policies
• Employee management
• Recruitment processes
• Company guidelines

Could you please be more specific about what you'd like to know? For example, you could ask "How do I apply for leave?" or "What are the claim policies?"`;
}

/**
 * Add message to chat window
 */
function addMessage(text, sender) {
  const messagesContainer = document.getElementById('chatbotMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${sender}`;
  
  if (sender === 'ai') {
    // Format AI messages with better line breaks
    messageDiv.innerHTML = formatAIMessage(text);
  } else {
    messageDiv.textContent = text;
  }
  
  messagesContainer.appendChild(messageDiv);
  
  // Scroll to bottom
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Format AI messages for better display
 */
function formatAIMessage(text) {
  // Convert basic formatting to HTML
  let formatted = text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold
    .replace(/\*(.*?)\*/g, '<em>$1</em>') // Italic
    .replace(/`(.*?)`/g, '<code style="background:#f0f0f0;padding:2px 4px;border-radius:3px;">$1</code>') // Code
    .replace(/\n\n/g, '<br><br>') // Double line breaks
    .replace(/\n/g, '<br>'); // Single line breaks
  
  return formatted;
}

/**
 * Show typing indicator
 */
function showTypingIndicator() {
  document.getElementById('typingIndicator').style.display = 'block';
  document.getElementById('sendBtn').disabled = true;
  
  const messagesContainer = document.getElementById('chatbotMessages');
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Hide typing indicator
 */
function hideTypingIndicator() {
  document.getElementById('typingIndicator').style.display = 'none';
  document.getElementById('sendBtn').disabled = false;
}

/**
 * Initialize chatbot when page loads
 */
document.addEventListener('DOMContentLoaded', function() {
  // Initialize the chatbot
  initializeChatbot();
  
  // Optional: Add a status message
  setTimeout(() => {
    if (document.getElementById('chatbotWindow') && document.getElementById('chatbotWindow').style.display !== 'flex') {
      console.log('AI HR Assistant ready! Click the robot icon to start chatting.');
    }
  }, 1000);
});