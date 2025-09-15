/**
 * SMEasyHR Universal AI Assistant
 * Handles any question while maintaining deep system knowledge
 */

// Google Gemini API Configuration
const AI_CONFIG = {
  apiKey: 'AIzaSyD8qlC9H2MT5N5wVlvud-XcIXBEmEV8OuA',
  apiUrl: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent',
  maxRetries: 2,
  timeout: 15000
};

// Enhanced System Knowledge Base
const SYSTEM_KNOWLEDGE = {
  // Current system state
  currentPage: '',
  userRole: '',
  systemData: {},
  
  // System features and capabilities
  features: {
    'employee-management': {
      path: '/employee-management',
      description: 'Manage employee records, profiles, and information',
      capabilities: ['add employees', 'edit profiles', 'view employee details', 'employee reports']
    },
    'leave-management': {
      path: '/leave-management', 
      description: 'Handle leave applications, approvals, and balance tracking',
      capabilities: ['apply leave', 'approve leave', 'check balance', 'leave history', 'cancel requests']
    },
    'payroll': {
      path: '/payroll',
      description: 'Salary processing, payslips, and payment management',
      capabilities: ['view payslips', 'salary reports', 'tax calculations', 'payment processing']
    },
    'attendance': {
      path: '/attendance',
      description: 'Time tracking, clock in/out, and attendance reports',
      capabilities: ['mark attendance', 'view records', 'overtime tracking', 'schedule management']
    },
    'claim-management': {
      path: '/claim-management',
      description: 'Expense claims, reimbursements, and approval workflow',
      capabilities: ['submit claims', 'upload receipts', 'track status', 'approve claims']
    },
    'recruitment': {
      path: '/recruitment',
      description: 'Hiring process, job postings, and candidate management',
      capabilities: ['post jobs', 'manage candidates', 'interview scheduling', 'hiring workflow']
    }
  },
  
  // Company policies and procedures
  policies: {
    leave: {
      annual: '21 days per year',
      sick: '14 days per year with medical certificate',
      maternity: '60 days as per labor law',
      emergency: 'Manager approval required',
      advance_notice: '3 days minimum for planned leave'
    },
    attendance: {
      working_hours: '9:00 AM - 6:00 PM (Monday-Friday)',
      grace_period: '15 minutes late arrival',
      lunch_break: '1 hour (12:00-1:00 PM typical)',
      overtime_rate: '1.5x regular hourly rate'
    },
    claims: {
      submission_deadline: '30 days from expense date',
      receipt_required: 'Original receipts mandatory',
      approval_workflow: 'Manager → Finance → Payment',
      categories: ['Transport', 'Meals', 'Medical', 'Office Supplies', 'Other Business']
    }
  }
};

// Chatbot state
let isTyping = false;
let conversationHistory = [];
let apiKeyTested = false;
let apiKeyValid = false;

// Debug mode
const DEBUG_MODE = true;

/**
 * Initialize comprehensive system integration
 */
function initializeSystemIntegration() {
  // Detect current page
  SYSTEM_KNOWLEDGE.currentPage = window.location.pathname;
  
  // Extract user information
  const userElements = document.querySelectorAll('.user-name, .username, [data-user-name], .navbar-nav .dropdown-toggle');
  userElements.forEach(el => {
    const text = el.textContent.trim();
    if (text && !text.includes('Dropdown')) {
      SYSTEM_KNOWLEDGE.userName = text;
    }
  });
  
  // Get system data from page
  detectSystemData();
  
  if (DEBUG_MODE) {
    console.log('🏢 Enhanced System Integration:', SYSTEM_KNOWLEDGE);
  }
}

/**
 * Enhanced system data detection
 */
function detectSystemData() {
  try {
    // Extract dashboard statistics
    const stats = {
      totalEmployees: extractStat(['[data-stat="employees"]', '.total-employee', '.employee-count']),
      pendingLeaves: extractStat(['[data-stat="leaves"]', '.total-leave', '.leave-pending']),
      pendingClaims: extractStat(['[data-stat="claims"]', '.total-claim', '.claim-pending'])
    };
    
    // Detect current page context
    const path = SYSTEM_KNOWLEDGE.currentPage.toLowerCase();
    let currentSection = 'Dashboard';
    
    Object.keys(SYSTEM_KNOWLEDGE.features).forEach(feature => {
      if (path.includes(feature.replace('-', ''))) {
        currentSection = SYSTEM_KNOWLEDGE.features[feature].description;
      }
    });
    
    SYSTEM_KNOWLEDGE.systemData = {
      ...stats,
      currentSection,
      pageUrl: window.location.href,
      timestamp: new Date().toISOString()
    };
    
  } catch (error) {
    console.log('Note: Could not extract all system data:', error.message);
  }
}

/**
 * Helper function to extract statistics from page
 */
function extractStat(selectors) {
  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element) {
      const text = element.textContent.trim();
      const number = text.match(/\d+/);
      return number ? number[0] : text;
    }
  }
  return 'N/A';
}

/**
 * Test API key on first load
 */
async function testAPIKey() {
  if (apiKeyTested) return apiKeyValid;
  
  console.log('🔍 Testing Gemini AI connection...');
  
  try {
    const response = await fetch(`${AI_CONFIG.apiUrl}?key=${AI_CONFIG.apiKey}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        contents: [{
          parts: [{
            text: "Hello, this is a connection test."
          }]
        }]
      })
    });
    
    apiKeyTested = true;
    
    if (response.ok) {
      const data = await response.json();
      console.log('✅ Gemini AI connected successfully');
      apiKeyValid = true;
      return true;
    } else {
      console.error('❌ AI connection failed:', response.status);
      apiKeyValid = false;
      return false;
    }
  } catch (error) {
    console.error('❌ AI connection error:', error);
    apiKeyTested = true;
    apiKeyValid = false;
    return false;
  }
}

/**
 * Initialize universal AI chatbot
 */
function initializeChatbot() {
  conversationHistory = [];
  
  // Initialize system integration
  initializeSystemIntegration();
  
  // Test AI connection
  testAPIKey().then(isConnected => {
    setTimeout(() => {
      if (document.getElementById('chatbotMessages')) {
        const welcomeMessage = generateUniversalWelcome(isConnected);
        addMessage(welcomeMessage, 'ai');
        addQuickActions();
      }
    }, 500);
  });
}

/**
 * Generate universal welcome message
 */
function generateUniversalWelcome(aiConnected) {
  const userName = SYSTEM_KNOWLEDGE.userName ? ` ${SYSTEM_KNOWLEDGE.userName}` : '';
  const currentSection = SYSTEM_KNOWLEDGE.systemData.currentSection || 'SMEasyHR System';
  
  let systemStatus = '';
  if (SYSTEM_KNOWLEDGE.systemData.totalEmployees !== 'N/A') {
    systemStatus = `\n\n📊 **System Overview:**\n• Employees: ${SYSTEM_KNOWLEDGE.systemData.totalEmployees}\n• Pending Leaves: ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves}\n• Pending Claims: ${SYSTEM_KNOWLEDGE.systemData.pendingClaims}`;
  }
  
  const aiStatus = aiConnected ? 
    '✅ **AI Status:** Fully operational with advanced capabilities!' : 
    '⚠️ **AI Status:** Basic mode (system help + simple responses)';
  
  return `Hello${userName}! 👋 Welcome to your Universal SMEasyHR Assistant.

${aiStatus}
📍 **Current Location:** ${currentSection}${systemStatus}

🌟 **I can help you with absolutely anything:**

**🏢 SMEasyHR System:**
• Navigate and use all HR features
• Apply for leave, check payslips, submit claims
• Understand policies and procedures
• Get step-by-step guidance

**🧠 General Knowledge:**
• Answer questions on any topic
• Help with calculations and analysis
• Provide explanations and tutorials
• Assist with problem-solving
• Creative writing and brainstorming
• Technical support and programming
• And much more!

Whether you need help with the HR system or have any other questions, I'm here to assist! What would you like to know?`;
}

/**
 * Enhanced quick actions based on context
 */
function addQuickActions() {
  const actionsContainer = document.createElement('div');
  actionsContainer.className = 'quick-actions';
  actionsContainer.innerHTML = `
    <div class="quick-actions-title">💡 Quick Actions & Examples:</div>
    <div class="quick-actions-buttons">
      ${generateUniversalQuickActions()}
    </div>
  `;
  
  const messagesContainer = document.getElementById('chatbotMessages');
  messagesContainer.appendChild(actionsContainer);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Generate universal quick actions
 */
function generateUniversalQuickActions() {
  const systemActions = [
    'How do I apply for leave?',
    'Show me my payslip',
    'Submit expense claim',
    'Check attendance record'
  ];
  
  const generalActions = [
    'What\'s the weather like?',
    'Explain quantum computing',
    'Help me write an email',
    'Calculate 15% of 2500'
  ];
  
  let buttons = '<div style="font-size: 11px; color: #666; margin-bottom: 5px;">🏢 System Help:</div>';
  systemActions.forEach(action => {
    buttons += `<button onclick="sendQuickMessage('${action}')" class="quick-btn">${action}</button>`;
  });
  
  buttons += '<div style="font-size: 11px; color: #666; margin: 10px 0 5px 0;">🌟 General AI:</div>';
  generalActions.forEach(action => {
    buttons += `<button onclick="sendQuickMessage('${action}')" class="quick-btn" style="background: #6f42c1;">${action}</button>`;
  });
  
  return buttons;
}

/**
 * Universal message processing with intelligent routing
 */
async function sendMessage() {
  const input = document.getElementById('chatbotInput');
  const message = input.value.trim();
  
  if (!message || isTyping) return;
  
  // Add user message
  addMessage(message, 'user');
  input.value = '';
  input.style.height = 'auto';
  
  // Show typing indicator
  showTypingIndicator();
  isTyping = true;
  
  console.log('🤖 Processing universal query:', message);
  
  try {
    let response = '';
    
    // Intelligent routing: Check if it's system-related first
    const isSystemQuery = detectSystemQuery(message);
    
    if (isSystemQuery) {
      console.log('🏢 Detected system query');
      response = getSystemResponse(message);
    }
    
    // If no system response or it's a general query, use AI
    if (!response && apiKeyValid) {
      console.log('🧠 Using AI for universal response');
      response = await getUniversalAIResponse(message, isSystemQuery);
    }
    
    // Enhanced fallback for any remaining cases
    if (!response) {
      response = getUniversalFallback(message);
    }
    
    // Update conversation history
    conversationHistory.push({ user: message, ai: response, type: isSystemQuery ? 'system' : 'general' });
    if (conversationHistory.length > 8) {
      conversationHistory = conversationHistory.slice(-8);
    }
    
    hideTypingIndicator();
    addMessage(response, 'ai');
    
    // Add contextual actions if needed
    if (response.includes('navigate') || isSystemQuery) {
      addContextualActions(message, isSystemQuery);
    }
    
  } catch (error) {
    console.error('Universal AI Error:', error);
    hideTypingIndicator();
    addMessage('I apologize for the technical difficulty. Please try rephrasing your question or ask something else!', 'ai');
  } finally {
    isTyping = false;
  }
}

/**
 * Detect if query is system-related
 */
function detectSystemQuery(message) {
  const systemKeywords = [
    'leave', 'payslip', 'salary', 'attendance', 'claim', 'expense', 'employee', 'hr', 'smeasyhr',
    'apply', 'submit', 'approve', 'dashboard', 'navigate', 'system', 'login', 'profile',
    'recruitment', 'hiring', 'overtime', 'policy', 'procedure', 'working hours', 'benefits'
  ];
  
  const messageLower = message.toLowerCase();
  return systemKeywords.some(keyword => messageLower.includes(keyword));
}

/**
 * Enhanced system response handler
 */
function getSystemResponse(message) {
  const messageLower = message.toLowerCase().trim();
  const currentSection = SYSTEM_KNOWLEDGE.systemData.currentSection;
  
  // Leave-related queries
  if (messageLower.includes('leave') && (messageLower.includes('apply') || messageLower.includes('how'))) {
    return generateLeaveGuide();
  }
  
  // Payslip queries
  if (messageLower.includes('payslip') || (messageLower.includes('salary') && messageLower.includes('view'))) {
    return generatePayslipGuide();
  }
  
  // Claims queries
  if (messageLower.includes('claim') || messageLower.includes('expense')) {
    return generateClaimGuide();
  }
  
  // Attendance queries
  if (messageLower.includes('attendance') || messageLower.includes('clock')) {
    return generateAttendanceGuide();
  }
  
  // Policy queries
  if (messageLower.includes('policy') || messageLower.includes('procedure')) {
    return generatePolicyInfo(messageLower);
  }
  
  // Dashboard/system overview
  if (messageLower.includes('dashboard') || messageLower.includes('overview')) {
    return generateSystemOverview();
  }
  
  // Navigation help
  if (messageLower.includes('navigate') || messageLower.includes('go to')) {
    return generateNavigationGuide();
  }
  
  return null; // Let AI handle it with system context
}

/**
 * Generate comprehensive leave application guide
 */
function generateLeaveGuide() {
  return `📝 **Complete Leave Application Guide**

**📋 Step-by-Step Process:**
1. Navigate to **"Leave Management"** section
2. Click **"Apply Leave"** button
3. Select leave type:
   • **Annual Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.annual}
   • **Sick Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.sick}
   • **Maternity Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.maternity}
   • **Emergency Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.emergency}
4. Choose start and end dates
5. Enter detailed reason
6. Submit for approval

**⚠️ Important Requirements:**
• Apply ${SYSTEM_KNOWLEDGE.policies.leave.advance_notice} for planned leave
• Medical certificate required for sick leave
• Emergency leave needs immediate manager approval

**💡 Pro Tips:**
• Check your leave balance before applying
• Plan around team schedules and deadlines
• Keep copies of your applications

${SYSTEM_KNOWLEDGE.systemData.currentSection !== 'Leave Management' ? '\n🎯 **[Navigate to Leave Management](leave-management)**' : '✅ You\'re already in Leave Management!'}`;
}

/**
 * Generate payslip access guide
 */
function generatePayslipGuide() {
  return `💰 **Payslip Access & Information Guide**

**📊 How to View Your Payslip:**
1. Go to **"Payroll"** section
2. Click **"View Payslip"**
3. Select desired month/period
4. Download PDF or view online

**📈 What's Included:**
• **Basic Salary:** Your monthly base pay
• **Allowances:** Transport, meal, housing allowances
• **Deductions:** Income tax, EPF contributions, insurance
• **Net Salary:** Final take-home amount
• **YTD Totals:** Year-to-date earnings and deductions

**📅 Important Dates:**
• Payslips available by 5th of each month
• Salary credited by 28th of each month
• Annual tax statements generated in January

**🔍 Need Help?**
• Contact Finance for payroll discrepancies
• HR for allowance questions
• IT for technical access issues

${SYSTEM_KNOWLEDGE.systemData.currentSection !== 'Payroll' ? '\n🎯 **[Navigate to Payroll](payroll)**' : '✅ You\'re in Payroll section!'}`;
}

/**
 * Generate expense claim guide
 */
function generateClaimGuide() {
  return `🧾 **Complete Expense Claim Guide**

**📝 Submission Process:**
1. Navigate to **"Claim Management"**
2. Click **"Submit New Claim"**
3. Select category: ${SYSTEM_KNOWLEDGE.policies.claims.categories.join(', ')}
4. Enter amount and detailed description
5. Upload original receipt (mandatory)
6. Submit for approval workflow

**📋 Claim Categories & Limits:**
• **Transport:** Taxi, parking, public transport
• **Meals:** Business meals, client entertainment
• **Medical:** Work-related medical expenses
• **Office Supplies:** Stationery, equipment
• **Other Business:** Conference fees, training costs

**⚠️ Critical Requirements:**
• Submit within ${SYSTEM_KNOWLEDGE.policies.claims.submission_deadline}
• ${SYSTEM_KNOWLEDGE.policies.claims.receipt_required}
• Amount must match receipt exactly
• Business purpose must be clearly stated

**🔄 Approval Process:**
${SYSTEM_KNOWLEDGE.policies.claims.approval_workflow}

**💡 Tips for Faster Processing:**
• Take clear, readable photos of receipts
• Provide detailed descriptions
• Submit regularly rather than bulk claims

${SYSTEM_KNOWLEDGE.systemData.currentSection !== 'Claim Management' ? '\n🎯 **[Navigate to Claim Management](claim-management)**' : '✅ You\'re in Claim Management!'}`;
}

/**
 * Generate attendance guide
 */
function generateAttendanceGuide() {
  return `⏰ **Comprehensive Attendance Guide**

**📅 Daily Process:**
• **Clock In:** Mark arrival (before 9:15 AM)
• **Breaks:** ${SYSTEM_KNOWLEDGE.policies.attendance.lunch_break}
• **Clock Out:** Mark departure (after 6:00 PM)

**🕘 Working Schedule:**
• **Regular Hours:** ${SYSTEM_KNOWLEDGE.policies.attendance.working_hours}
• **Grace Period:** ${SYSTEM_KNOWLEDGE.policies.attendance.grace_period}
• **Overtime Rate:** ${SYSTEM_KNOWLEDGE.policies.attendance.overtime_rate}

**📊 Features Available:**
• **Real-time Tracking:** GPS location verification
• **Monthly Reports:** Detailed attendance summaries
• **Overtime Calculation:** Automatic computation
• **Leave Integration:** Seamless with leave management

**🚨 Important Notes:**
• Late arrivals beyond grace period are marked
• Overtime requires prior manager approval
• Weekend work needs special authorization
• Public holidays are automatically excluded

**💡 Best Practices:**
• Mark attendance consistently
• Report technical issues immediately
• Keep backup records for important days

${SYSTEM_KNOWLEDGE.systemData.currentSection !== 'Attendance' ? '\n🎯 **[Navigate to Attendance](attendance)**' : '✅ You\'re in Attendance section!'}`;
}

/**
 * Generate policy information
 */
function generatePolicyInfo(query) {
  let policyInfo = '📋 **Company Policies & Procedures**\n\n';
  
  if (query.includes('leave')) {
    policyInfo += `**🏖️ Leave Policies:**\n`;
    Object.entries(SYSTEM_KNOWLEDGE.policies.leave).forEach(([key, value]) => {
      policyInfo += `• **${key.replace('_', ' ').toUpperCase()}:** ${value}\n`;
    });
  } else if (query.includes('attendance')) {
    policyInfo += `**⏰ Attendance Policies:**\n`;
    Object.entries(SYSTEM_KNOWLEDGE.policies.attendance).forEach(([key, value]) => {
      policyInfo += `• **${key.replace('_', ' ').toUpperCase()}:** ${value}\n`;
    });
  } else {
    policyInfo += `**📚 Available Policy Information:**\n• Leave policies and entitlements\n• Attendance and working hours\n• Expense claim procedures\n• Performance evaluation guidelines\n• Code of conduct and ethics\n\nWhat specific policy would you like to know about?`;
  }
  
  return policyInfo;
}

/**
 * Generate system overview
 */
function generateSystemOverview() {
  return `📊 **SMEasyHR System Overview**

**📈 Current Statistics:**
• **Total Employees:** ${SYSTEM_KNOWLEDGE.systemData.totalEmployees}
• **Pending Leaves:** ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves} applications
• **Pending Claims:** ${SYSTEM_KNOWLEDGE.systemData.pendingClaims} submissions

**🏢 System Modules:**
${Object.entries(SYSTEM_KNOWLEDGE.features).map(([key, feature]) => 
  `• **${feature.description}**\n  ${feature.capabilities.join(', ')}`
).join('\n')}

**🎯 Your Current Location:** ${SYSTEM_KNOWLEDGE.systemData.currentSection}

**💡 What You Can Do:**
• Navigate between modules seamlessly
• Access real-time data and reports
• Manage all HR tasks efficiently
• Get instant help and guidance

Need help with any specific module or task?`;
}

/**
 * Generate navigation guide
 */
function generateNavigationGuide() {
  return `🧭 **System Navigation Guide**

**🏠 Main Sections:**
${Object.entries(SYSTEM_KNOWLEDGE.features).map(([key, feature]) => 
  `• **${feature.description}** - ${feature.capabilities.slice(0, 2).join(', ')}`
).join('\n')}

**⚡ Quick Navigation Tips:**
• Use sidebar menu for main sections
• Look for action buttons in each module
• Use breadcrumbs to track your location
• Search functionality available in most sections

**🎯 Current Location:** ${SYSTEM_KNOWLEDGE.systemData.currentSection}

Which section would you like to visit? I can guide you there!`;
}

/**
 * Universal AI response with enhanced context
 */
async function getUniversalAIResponse(message, isSystemQuery) {
  for (let attempt = 0; attempt < AI_CONFIG.maxRetries; attempt++) {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), AI_CONFIG.timeout);
      
      const enhancedPrompt = buildUniversalPrompt(message, isSystemQuery);
      
      const requestBody = {
        contents: [{
          parts: [{
            text: enhancedPrompt
          }]
        }],
        generationConfig: {
          temperature: 0.8,
          topK: 40,
          topP: 0.95,
          maxOutputTokens: 300
        }
      };
      
      const response = await fetch(`${AI_CONFIG.apiUrl}?key=${AI_CONFIG.apiKey}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody),
        signal: controller.signal
      });
      
      clearTimeout(timeoutId);
      
      if (response.ok) {
        const data = await response.json();
        
        if (data.candidates && data.candidates[0] && data.candidates[0].content) {
          const aiText = data.candidates[0].content.parts[0].text.trim();
          
          if (aiText && aiText.length > 10) {
            // Add system context if it's a system query
            if (isSystemQuery && !aiText.includes('SMEasyHR')) {
              return `${aiText}\n\n💡 *This information is enhanced with SMEasyHR system knowledge. Need specific system help? Just ask!*`;
            }
            return aiText;
          }
        }
      }
      
    } catch (error) {
      console.error('Universal AI Error:', error);
    }
  }
  
  return null;
}

/**
 * Build universal prompt with system awareness
 */
function buildUniversalPrompt(message, isSystemQuery) {
  let prompt = '';
  
  if (isSystemQuery) {
    prompt = `You are an expert SMEasyHR system assistant with deep knowledge of HR processes. Current context:
- User is in: ${SYSTEM_KNOWLEDGE.systemData.currentSection}
- System stats: ${SYSTEM_KNOWLEDGE.systemData.totalEmployees} employees, ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves} pending leaves, ${SYSTEM_KNOWLEDGE.systemData.pendingClaims} pending claims
- Available modules: Employee Management, Leave Management, Payroll, Attendance, Claim Management, Recruitment

User question: ${message}

Provide helpful, specific guidance about the SMEasyHR system. Include step-by-step instructions when relevant.`;
  } else {
    prompt = `You are a knowledgeable AI assistant that can help with any topic. Be conversational, helpful, and provide accurate information.

Previous conversation context:
${conversationHistory.slice(-2).map(h => `User: ${h.user}\nAssistant: ${h.ai}`).join('\n')}

Current question: ${message}

Provide a helpful, engaging response. If this relates to HR or business systems, you can mention that you also have specialized knowledge of SMEasyHR.`;
  }
  
  return prompt;
}

/**
 * Universal fallback response
 */
function getUniversalFallback(message) {
  const messageLower = message.toLowerCase().trim();
  
  // Math calculations
  const mathResult = calculateMath(messageLower);
  if (mathResult) return mathResult;
  
  // Basic conversational responses
  if (messageLower.match(/^(hi|hello|hey)/)) {
    return `Hello! 👋 I'm your universal SMEasyHR assistant. I can help you with the HR system, answer general questions, solve problems, or chat about any topic. What's on your mind?`;
  }
  
  if (messageLower.includes('weather')) {
    return `I don't have access to real-time weather data, but I can help you find weather information! Try checking a weather app or website like weather.com. Is there anything else I can help you with? Maybe something about the SMEasyHR system or any other topic?`;
  }
  
  if (messageLower.includes('time') || messageLower.includes('date')) {
    const now = new Date();
    return `Current time: ${now.toLocaleTimeString()}\nCurrent date: ${now.toLocaleDateString()}\n\nIs there anything specific you'd like to know or do in the SMEasyHR system?`;
  }
  
  // Default universal response
  return `I understand you're asking about "${message}". While I'm experiencing some connectivity issues with my AI capabilities, I can still help you with:

🏢 **SMEasyHR System:**
• Complete guidance on all HR features
• Step-by-step instructions for common tasks
• Policy information and procedures
• System navigation and troubleshooting

💡 **General Assistance:**
• Basic calculations and problem-solving
• Simple questions and explanations
• System recommendations

What would you like to know more about? I'm here to help with anything!`;
}

/**
 * Calculate math expressions
 */
function calculateMath(message) {
  // Addition
  let match = message.match(/(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)/);
  if (match) {
    const result = parseFloat(match[1]) + parseFloat(match[2]);
    return `${match[1]} + ${match[2]} = ${result} 🧮`;
  }
  
  // Subtraction
  match = message.match(/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/);
  if (match) {
    const result = parseFloat(match[1]) - parseFloat(match[2]);
    return `${match[1]} - ${match[2]} = ${result} 🧮`;
  }
  
  // Multiplication
  match = message.match(/(\d+(?:\.\d+)?)\s*[\*×]\s*(\d+(?:\.\d+)?)/);
  if (match) {
    const result = parseFloat(match[1]) * parseFloat(match[2]);
    return `${match[1]} × ${match[2]} = ${result} 🧮`;
  }
  
  // Division
  match = message.match(/(\d+(?:\.\d+)?)\s*[\/÷]\s*(\d+(?:\.\d+)?)/);
  if (match) {
    const result = parseFloat(match[1]) / parseFloat(match[2]);
    return `${match[1]} ÷ ${match[2]} = ${result} 🧮`;
  }
  
  // Percentage
  match = message.match(/(\d+(?:\.\d+)?)\s*%\s*of\s*(\d+(?:\.\d+)?)/);
  if (match) {
    const result = (parseFloat(match[1]) / 100) * parseFloat(match[2]);
    return `${match[1]}% of ${match[2]} = ${result} 🧮`;
  }
  
  return null;
}

/**
 * Add contextual actions based on response
 */
function addContextualActions(message, isSystemQuery) {
  if (isSystemQuery) {
    addNavigationButtons();
  } else {
    // Add general exploration suggestions
    const exploreContainer = document.createElement('div');
    exploreContainer.className = 'quick-actions';
    exploreContainer.innerHTML = `
      <div class="quick-actions-title">💡 Explore More:</div>
      <div class="quick-actions-buttons">
        <button onclick="sendQuickMessage('Tell me about artificial intelligence')" class="quick-btn" style="background: #6f42c1;">🤖 AI Topics</button>
        <button onclick="sendQuickMessage('How does the internet work?')" class="quick-btn" style="background: #6f42c1;">🌐 Technology</button>
        <button onclick="sendQuickMessage('Explain photosynthesis')" class="quick-btn" style="background: #6f42c1;">🧬 Science</button>
        <button onclick="sendQuickMessage('Navigate to different section')" class="quick-btn">🧭 System Help</button>
      </div>
    `;
    
    const messagesContainer = document.getElementById('chatbotMessages');
    messagesContainer.appendChild(exploreContainer);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

/**
 * Enhanced navigation buttons
 */
function addNavigationButtons() {
  const navContainer = document.createElement('div');
  navContainer.className = 'navigation-buttons';
  navContainer.innerHTML = `
    <div class="nav-title">🚀 Quick System Navigation:</div>
    <div class="nav-buttons">
      <button onclick="navigateToSection('leave-management')" class="nav-btn">📅 Leave</button>
      <button onclick="navigateToSection('payroll')" class="nav-btn">💰 Payroll</button>
      <button onclick="navigateToSection('attendance')" class="nav-btn">⏰ Attendance</button>
      <button onclick="navigateToSection('claim-management')" class="nav-btn">🧾 Claims</button>
      <button onclick="navigateToSection('employee-management')" class="nav-btn">👥 Employees</button>
      <button onclick="navigateToSection('recruitment')" class="nav-btn">🎯 Recruitment</button>
    </div>
  `;
  
  const messagesContainer = document.getElementById('chatbotMessages');
  messagesContainer.appendChild(navContainer);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Navigate to system section
 */
function navigateToSection(section) {
  const feature = SYSTEM_KNOWLEDGE.features[section];
  if (feature) {
    addMessage(`🚀 Navigating to ${feature.description}...`, 'ai');
    setTimeout(() => {
      window.location.href = feature.path;
    }, 1000);
  }
}

// Enhanced UI Functions with universal formatting
function addMessage(text, sender) {
  const messagesContainer = document.getElementById('chatbotMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${sender}`;
  
  if (sender === 'ai') {
    messageDiv.innerHTML = formatUniversalMessage(text);
  } else {
    messageDiv.textContent = text;
  }
  
  messagesContainer.appendChild(messageDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function formatUniversalMessage(text) {
  return text
    .replace(/\*\*(.*?)\*\*/g, '<strong style="color:#007bff;">$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code style="background:#f0f0f0;padding:2px 4px;border-radius:3px;font-family:monospace;color:#e83e8c;">$1</code>')
    .replace(/\n\n/g, '<br><br>')
    .replace(/\n/g, '<br>')
    .replace(/•/g, '<span style="color:#007bff;font-weight:bold;">•</span>')
    .replace(/(👋|😊|💻|📚|🔬|💼|🎯|🏢|🌟|🚀|💪|🔧|📖|💬|🎮|🌍|🤔|💭|🌤️|🧮|✅|❌|⚠️|✍️|🧠|📝|💰|🧾|⏰|📊|🧭|👥|📅|📋|🏠|💡|🔍|📈|📋|🎨|🔥|⚡|🎪|🌈)/g, '<span style="font-size:1.1em;">$&</span>')
    .replace(/\[Navigate to (.*?)\]\((.*?)\)/g, '<button onclick="navigateToSection(\'$2\')" class="inline-nav-btn">🎯 Go to $1</button>');
}

// Keep all existing UI functions
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

function handleKeyPress(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

function autoResize(textarea) {
  textarea.style.height = 'auto';
  textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
}

function sendQuickMessage(message) {
  document.getElementById('chatbotInput').value = message;
  sendMessage();
}

function showTypingIndicator() {
  document.getElementById('typingIndicator').style.display = 'block';
  document.getElementById('sendBtn').disabled = true;
  const messagesContainer = document.getElementById('chatbotMessages');
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
  document.getElementById('typingIndicator').style.display = 'none';
  document.getElementById('sendBtn').disabled = false;
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('🌟 Universal SMEasyHR AI Assistant initializing...');
  initializeChatbot();
});