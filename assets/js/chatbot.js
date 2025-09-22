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
  
  // System features and capabilities with role-based paths
  features: {
    'employee-management': {
      employee_path: '/view_employee.php',
      employer_path: '/view_all.php',
      description: 'Manage employee records, profiles, and information',
      employee_capabilities: ['view my profile', 'edit personal details', 'update contact info', 'view employment details'],
      employer_capabilities: ['add employees', 'edit profiles', 'view employee details', 'employee reports', 'manage departments']
    },
    'leave-management': {
      employee_path: '/apply_leave.php', 
      employer_path: '/AL.php',
      description: 'Handle leave applications, approvals, and balance tracking',
      employee_capabilities: ['apply leave', 'check balance', 'view leave history', 'cancel pending requests'],
      employer_capabilities: ['approve leave', 'reject leave', 'view team leave', 'leave reports', 'manage leave policies']
    },
    'payroll': {
      employee_path: '/EC_payslip.php',
      employer_path: '/P_payroll.php',
      description: 'Salary processing, payslips, and payment management',
      employee_capabilities: ['view payslips', 'download tax forms', 'check salary history', 'update bank details'],
      employer_capabilities: ['process payroll', 'salary reports', 'tax calculations', 'manage allowances', 'bulk payments']
    },
    'attendance': {
      employee_path: '/attendance_employee.php',
      employer_path: '/attendance_employer.php',
      description: 'Time tracking, clock in/out, and attendance reports',
      employee_capabilities: ['clock in/out', 'view my records', 'request corrections', 'overtime tracking'],
      employer_capabilities: ['view team attendance', 'approve corrections', 'attendance reports', 'schedule management']
    },
    'claim-management': {
      employee_path: '/ER_claim.php',
      employer_path: '/R_claim.php',
      description: 'Expense claims, reimbursements, and approval workflow',
      employee_capabilities: ['submit claims', 'upload receipts', 'track status', 'view claim history'],
      employer_capabilities: ['approve claims', 'reject claims', 'claim reports', 'set claim limits', 'audit trails']
    },
    'recruitment': {
      // No employee_path - recruitment is employer-only
      employer_path: '/recruitment_process.php',
      description: 'Hiring process, job postings, and candidate management',
      employee_capabilities: [], // Empty for employees
      employer_capabilities: ['post jobs', 'manage candidates', 'interview scheduling', 'hiring workflow', 'recruitment analytics']
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

/**
 * Detect user role from page context or session
 */
function detectUserRole() {
  // Method 1: Check URL patterns
  const currentPath = window.location.pathname.toLowerCase();
  if (currentPath.includes('employer') || currentPath.includes('admin') || currentPath.includes('manager')) {
    return 'employer';
  }
  
  // Method 2: Check for role indicators in the page
  const roleElements = document.querySelectorAll('[data-role], .user-role, .role-badge');
  for (const element of roleElements) {
    const roleText = element.textContent.toLowerCase();
    if (roleText.includes('admin') || roleText.includes('manager') || roleText.includes('hr')) {
      return 'employer';
    }
  }
  
  // Method 3: Check navigation menu structure
  const navLinks = document.querySelectorAll('nav a, .sidebar a');
  const hasEmployerFeatures = Array.from(navLinks).some(link => 
    link.href.includes('employee-management') || 
    link.href.includes('payroll-management') ||
    link.textContent.toLowerCase().includes('manage')
  );
  
  if (hasEmployerFeatures) {
    return 'employer';
  }
  
  // Default to employee if unclear
  return 'employee';
}

/**
 * Get the correct path and capabilities based on user role
 */
function getFeatureForRole(featureKey) {
  const feature = SYSTEM_KNOWLEDGE.features[featureKey];
  if (!feature) return null;
  
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  
  // Special handling for recruitment - employees don't have access
  if (featureKey === 'recruitment' && userRole === 'employee') {
    return null; // Return null to indicate no access
  }
  
  return {
    path: userRole === 'employer' ? feature.employer_path : feature.employee_path,
    description: feature.description,
    capabilities: userRole === 'employer' ? feature.employer_capabilities : feature.employee_capabilities,
    userRole: userRole,
    hasAccess: userRole === 'employer' || featureKey !== 'recruitment'
  };
}

/**
 * Handle recruitment-related queries for employees
 */
function handleRecruitmentQuery(message, userRole) {
  if (userRole === 'employee' && 
      (message.toLowerCase().includes('recruitment') || 
       message.toLowerCase().includes('hiring') || 
       message.toLowerCase().includes('job post'))) {
    
    return `🚫 **Recruitment Access Notice**

**For Employee Users:**
Recruitment and hiring features are restricted to management and HR personnel only.

**What You Can Do Instead:**
• **Employee Referrals:** Recommend qualified candidates to HR
• **Internal Opportunities:** Check with HR about internal job postings
• **Career Development:** Discuss growth opportunities with your manager
• **Skills Enhancement:** Access training resources through the system

**📞 Need Help?**
Contact your HR department or manager for:
• Referring potential candidates
• Questions about internal opportunities
• Career development discussions
• Training and development programs

**🎯 Available Employee Features:**
• Leave Management - Apply and track leave
• Payroll - View payslips and salary information
• Attendance - Clock in/out and view records
• Expense Claims - Submit and track reimbursements
• Profile Management - Update personal information`;
  }
  
  return null;
}

// Chatbot state
let isTyping = false;
let conversationHistory = [];
let apiKeyTested = false;
let apiKeyValid = false;
let chatbotInitialized = false;

// Debug mode
const DEBUG_MODE = true;

/**
 * ROBUST: Create or get typing indicator element
 */
function getOrCreateTypingIndicator() {
  let indicator = document.getElementById('typingIndicator');
  
  if (!indicator) {
    console.log('⚡ Creating typing indicator');
    const messagesContainer = document.getElementById('chatbotMessages');
    
    if (!messagesContainer) {
      console.error('❌ Cannot create typing indicator - messages container not found');
      return null;
    }
    
    indicator = document.createElement('div');
    indicator.id = 'typingIndicator';
    indicator.className = 'message ai typing-indicator';
    indicator.style.display = 'none';
    indicator.innerHTML = `
      <div class="typing-dots" style="display: flex; gap: 4px; align-items: center;">
        <div class="dot" style="width: 8px; height: 8px; background: #007bff; border-radius: 50%; animation: typing 1.4s infinite ease-in-out;"></div>
        <div class="dot" style="width: 8px; height: 8px; background: #007bff; border-radius: 50%; animation: typing 1.4s infinite ease-in-out 0.2s;"></div>
        <div class="dot" style="width: 8px; height: 8px; background: #007bff; border-radius: 50%; animation: typing 1.4s infinite ease-in-out 0.4s;"></div>
        <span style="margin-left: 10px; color: #666; font-style: italic;">AI is thinking...</span>
      </div>
    `;
    
    // Add CSS animation if not already present
    if (!document.getElementById('typing-animation-style')) {
      const style = document.createElement('style');
      style.id = 'typing-animation-style';
      style.textContent = `
        @keyframes typing {
          0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
          30% { transform: translateY(-10px); opacity: 1; }
        }
      `;
      document.head.appendChild(style);
    }
    
    messagesContainer.appendChild(indicator);
  }
  
  return indicator;
}

/**
 * ENHANCED: Ensure all required DOM elements exist with robust checking
 */
function ensureChatbotDOM() {
  const chatbotWindow = document.getElementById('chatbotWindow');
  if (!chatbotWindow) {
    console.error('❌ Chatbot window not found in DOM');
    return false;
  }

  const messagesContainer = document.getElementById('chatbotMessages');
  if (!messagesContainer) {
    console.error('❌ Messages container not found');
    return false;
  }

  const chatbotInput = document.getElementById('chatbotInput');
  if (!chatbotInput) {
    console.error('❌ Input element not found');
    return false;
  }

  // Ensure typing indicator exists
  const indicator = getOrCreateTypingIndicator();
  if (!indicator) {
    console.warn('⚠️ Could not create typing indicator');
    // Don't return false - chatbot can still work without typing indicator
  }

  return true;
}

/**
 * SAFE: Show typing indicator with null checks
 */
function showTypingIndicator() {
  try {
    const indicator = getOrCreateTypingIndicator();
    if (indicator) {
      indicator.style.display = 'block';
      console.log('✅ Typing indicator shown');
    } else {
      console.warn('⚠️ Cannot show typing indicator - element not available');
    }
    
    const sendBtn = document.getElementById('sendBtn');
    if (sendBtn) {
      sendBtn.disabled = true;
    }
    
    const messagesContainer = document.getElementById('chatbotMessages');
    if (messagesContainer) {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
  } catch (error) {
    console.warn('⚠️ Error showing typing indicator:', error.message);
    // Continue without typing indicator
  }
}

/**
 * SAFE: Hide typing indicator with null checks
 */
function hideTypingIndicator() {
  try {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
      indicator.style.display = 'none';
      console.log('✅ Typing indicator hidden');
    }
    
    const sendBtn = document.getElementById('sendBtn');
    if (sendBtn) {
      sendBtn.disabled = false;
    }
  } catch (error) {
    console.warn('⚠️ Error hiding typing indicator:', error.message);
    // Continue without typing indicator
  }
}

/**
 * Initialize comprehensive system integration
 */
function initializeSystemIntegration() {
  // Detect current page
  SYSTEM_KNOWLEDGE.currentPage = window.location.pathname;
  
  // Detect user role
  SYSTEM_KNOWLEDGE.userRole = detectUserRole();
  
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
    console.log('👤 Detected User Role:', SYSTEM_KNOWLEDGE.userRole);
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
 * Enhanced debugging and error handling
 */
function debugChatbot() {
  console.log('🔍 Chatbot Debug Information:');
  console.log('- Initialized:', chatbotInitialized);
  console.log('- API Key Valid:', apiKeyValid);
  console.log('- Is Typing:', isTyping);
  console.log('- Conversation History:', conversationHistory.length, 'messages');
  console.log('- System Knowledge:', SYSTEM_KNOWLEDGE);
  
  // Test DOM elements
  const elements = {
    chatbotWindow: document.getElementById('chatbotWindow'),
    chatbotInput: document.getElementById('chatbotInput'),
    chatbotMessages: document.getElementById('chatbotMessages'),
    sendBtn: document.getElementById('sendBtn'),
    typingIndicator: document.getElementById('typingIndicator')
  };
  
  console.log('- DOM Elements:', elements);
  
  Object.entries(elements).forEach(([name, element]) => {
    if (!element) {
      console.error(`❌ Missing element: ${name}`);
    } else {
      console.log(`✅ Found element: ${name}`);
    }
  });
}

/**
 * Add this function to test the chatbot
 */
function testChatbot() {
  console.log('🧪 Testing chatbot functionality...');
  debugChatbot();
  
  // Test sending a simple message
  const input = document.getElementById('chatbotInput');
  if (input) {
    input.value = 'Hello, this is a test message';
    sendMessage();
  } else {
    console.error('❌ Cannot test - input element not found');
  }
}

/**
 * ENHANCED: Send message with comprehensive error handling
 */
async function sendMessage() {
  console.log('🚀 SendMessage called');
  
  try {
    // Ensure DOM elements exist
    if (!ensureChatbotDOM()) {
      console.error('❌ Required DOM elements missing, cannot send message');
      return;
    }
    
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    console.log('📝 Message:', message);
    
    if (!message) {
      console.log('⚠️ Empty message, returning');
      return;
    }
    
    if (isTyping) {
      console.log('⚠️ Already typing, returning');
      return;
    }
    
    // Add user message
    console.log('➕ Adding user message');
    addMessage(message, 'user');
    input.value = '';
    input.style.height = 'auto';
    
    // Show typing indicator (with error handling)
    console.log('⌨️ Showing typing indicator');
    showTypingIndicator();
    isTyping = true;
    
    console.log('🤖 Processing universal query:', message);
    
    let response = '';
    
    // Intelligent routing: Check if it's system-related first
    const isSystemQuery = detectSystemQuery(message);
    console.log('🔍 Is system query:', isSystemQuery);
    
    if (isSystemQuery) {
      console.log('🏢 Getting system response');
      response = getSystemResponse(message);
      console.log('📋 System response:', response ? 'Generated' : 'None');
    }
    
    // If no system response or it's a general query, use AI
    if (!response && apiKeyValid) {
      console.log('🧠 Using AI for universal response');
      response = await getUniversalAIResponse(message, isSystemQuery);
      console.log('🤖 AI response:', response ? 'Generated' : 'Failed');
    }
    
    // Enhanced fallback for any remaining cases
    if (!response) {
      console.log('🛡️ Using fallback response');
      response = getUniversalFallback(message);
    }
    
    console.log('✅ Final response ready:', response.substring(0, 50) + '...');
    
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
    
    console.log('✅ Message processing complete');
    
  } catch (error) {
    console.error('❌ Universal AI Error:', error);
    hideTypingIndicator();
    addMessage('I apologize for the technical difficulty. Please try rephrasing your question or ask something else!', 'ai');
  } finally {
    isTyping = false;
    console.log('🏁 SendMessage finished, isTyping reset');
  }
}

/**
 * Initialize universal AI chatbot
 */
function initializeChatbot() {
  if (chatbotInitialized) {
    console.log('🔄 Chatbot already initialized, skipping...');
    return;
  }
  
  console.log('🌟 Initializing Universal SMEasyHR AI Assistant...');
  
  if (!ensureChatbotDOM()) {
    console.error('❌ Cannot initialize chatbot - required DOM elements missing');
    return;
  }
  
  chatbotInitialized = true;
  
  // Clear any existing messages
  const messagesContainer = document.getElementById('chatbotMessages');
  if (messagesContainer) {
    const existingMessages = messagesContainer.querySelectorAll('.message:not(.typing-indicator)');
    existingMessages.forEach(msg => msg.remove());
  }
  
  // Reset conversation history
  conversationHistory = [];
  
  // Initialize system integration
  initializeSystemIntegration();
  
  // Test AI connection and show welcome message
  testAPIKey().then(isConnected => {
    const container = document.getElementById('chatbotMessages');
    const nonTypingMessages = container.querySelectorAll('.message:not(.typing-indicator)');
    
    if (container && nonTypingMessages.length === 0) {
      const welcomeMessage = generateUniversalWelcome(isConnected);
      addMessage(welcomeMessage, 'ai');
      addQuickActions();
    }
  });
}

/**
 * Generate universal welcome message
 */
function generateUniversalWelcome(aiConnected) {
  const userName = SYSTEM_KNOWLEDGE.userName ? ` ${SYSTEM_KNOWLEDGE.userName}` : '';
  const currentSection = SYSTEM_KNOWLEDGE.systemData.currentSection || 'SMEasyHR System';
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleDisplay = userRole === 'employer' ? 'Manager/HR' : 'Employee';
  
  let systemStatus = '';
  if (SYSTEM_KNOWLEDGE.systemData.totalEmployees !== 'N/A') {
    systemStatus = `\n\n📊 **System Overview:**\n• Employees: ${SYSTEM_KNOWLEDGE.systemData.totalEmployees}\n• Pending Leaves: ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves}\n• Pending Claims: ${SYSTEM_KNOWLEDGE.systemData.pendingClaims}`;
  }
  
  const aiStatus = aiConnected ? 
    '✅ **AI Status:** Fully operational with advanced capabilities!' : 
    '⚠️ **AI Status:** Basic mode (system help + simple responses)';
  
  return `Hello${userName}! 👋 Welcome to your Universal SMEasyHR Assistant.

${aiStatus}
📍 **Current Location:** ${currentSection}
👤 **Access Level:** ${roleDisplay}${systemStatus}

🌟 **I can help you with absolutely anything:**

**🏢 SMEasyHR System:**
• Navigate and use all HR features appropriate to your role
• ${userRole === 'employer' ? 'Manage teams, approve requests, generate reports' : 'Apply for leave, check payslips, submit claims'}
• Understand policies and procedures
• Get step-by-step guidance for your access level

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
  if (messagesContainer) {
    messagesContainer.appendChild(actionsContainer);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

/**
 * Generate universal quick actions
 */
function generateUniversalQuickActions() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  
  const systemActions = userRole === 'employer' ? [
    'How do I approve leave requests?',
    'Generate payroll reports',
    'View team attendance',
    'Manage employee claims'
  ] : [
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
  
  let buttons = `<div style="font-size: 11px; color: #666; margin-bottom: 5px;">🏢 ${userRole === 'employer' ? 'Management' : 'Employee'} Help:</div>`;
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
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  
  // Check for recruitment queries from employees
  const recruitmentResponse = handleRecruitmentQuery(message, userRole);
  if (recruitmentResponse) {
    return recruitmentResponse;
  }
  
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
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleFeature = getFeatureForRole('leave-management');
  
  return `📝 **Complete Leave Application Guide**

**📋 Step-by-Step Process:**
1. Navigate to **"Leave Management"** section
2. Click **"${userRole === 'employer' ? 'Manage Leave Requests' : 'Apply Leave'}"** button
3. ${userRole === 'employer' ? 'Review and approve/reject applications' : 'Select leave type:'}
   ${userRole === 'employee' ? `• **Annual Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.annual}
   • **Sick Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.sick}
   • **Maternity Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.maternity}
   • **Emergency Leave:** ${SYSTEM_KNOWLEDGE.policies.leave.emergency}` : ''}
${userRole === 'employee' ? '4. Choose start and end dates\n5. Enter detailed reason\n6. Submit for approval' : '4. Check team availability\n5. Approve or provide feedback\n6. Update leave balance'}

**⚠️ Important Requirements:**
${userRole === 'employee' ? `• Apply ${SYSTEM_KNOWLEDGE.policies.leave.advance_notice} for planned leave
• Medical certificate required for sick leave
• Emergency leave needs immediate manager approval` : `• Review applications promptly
• Consider team coverage and workload
• Maintain fair leave distribution`}

**💡 Pro Tips:**
${userRole === 'employee' ? `• Check your leave balance before applying
• Plan around team schedules and deadlines
• Keep copies of your applications` : `• Set up automated notifications
• Maintain leave calendar visibility
• Document approval reasons`}

🎯 **[Navigate to ${roleFeature ? roleFeature.description : 'Leave Management'}](${roleFeature ? roleFeature.path : '/leave-management'})**`;
}

/**
 * Generate payslip access guide
 */
function generatePayslipGuide() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleFeature = getFeatureForRole('payroll');
  
  return `💰 **${userRole === 'employer' ? 'Payroll Management' : 'Payslip Access'} Guide**

**📊 How to ${userRole === 'employer' ? 'Manage Payroll' : 'View Your Payslip'}:**
1. Go to **"Payroll"** section
2. Click **"${userRole === 'employer' ? 'Process Payroll' : 'View Payslip'}"**
3. ${userRole === 'employer' ? 'Select pay period and employees' : 'Select desired month/period'}
4. ${userRole === 'employer' ? 'Review and approve payments' : 'Download PDF or view online'}

**📈 ${userRole === 'employer' ? 'Payroll Components' : "What's Included"}:**
• **Basic Salary:** ${userRole === 'employer' ? 'Employee base pay rates' : 'Your monthly base pay'}
• **Allowances:** Transport, meal, housing allowances
• **Deductions:** Income tax, EPF contributions, insurance
• **Net Salary:** ${userRole === 'employer' ? 'Final employee payments' : 'Final take-home amount'}
• **YTD Totals:** Year-to-date earnings and deductions

**📅 Important Dates:**
• Payslips available by 5th of each month
• Salary credited by 28th of each month
• Annual tax statements generated in January

**🔍 Need Help?**
• Contact Finance for payroll discrepancies
• HR for allowance questions
• IT for technical access issues

🎯 **[Navigate to ${roleFeature ? roleFeature.description : 'Payroll'}](${roleFeature ? roleFeature.path : '/payroll'})**`;
}

/**
 * Generate expense claim guide
 */
function generateClaimGuide() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleFeature = getFeatureForRole('claim-management');
  
  return `🧾 **Complete ${userRole === 'employer' ? 'Claim Management' : 'Expense Claim'} Guide**

**📝 ${userRole === 'employer' ? 'Approval Process' : 'Submission Process'}:**
1. Navigate to **"Claim Management"**
2. Click **"${userRole === 'employer' ? 'Review Claims' : 'Submit New Claim'}"**
3. ${userRole === 'employer' ? 'Review submitted claims and receipts' : `Select category: ${SYSTEM_KNOWLEDGE.policies.claims.categories.join(', ')}`}
4. ${userRole === 'employer' ? 'Approve, reject, or request clarification' : 'Enter amount and detailed description'}
5. ${userRole === 'employer' ? 'Process approved claims for payment' : 'Upload original receipt (mandatory)'}
6. ${userRole === 'employer' ? 'Generate claim reports' : 'Submit for approval workflow'}

**📋 Claim Categories & ${userRole === 'employer' ? 'Management' : 'Limits'}:**
• **Transport:** Taxi, parking, public transport
• **Meals:** Business meals, client entertainment
• **Medical:** Work-related medical expenses
• **Office Supplies:** Stationery, equipment
• **Other Business:** Conference fees, training costs

**⚠️ Critical Requirements:**
${userRole === 'employee' ? `• Submit within ${SYSTEM_KNOWLEDGE.policies.claims.submission_deadline}
• ${SYSTEM_KNOWLEDGE.policies.claims.receipt_required}
• Amount must match receipt exactly
• Business purpose must be clearly stated` : `• Review claims within 5 business days
• Verify receipt authenticity and relevance
• Ensure compliance with company policies
• Maintain audit trail for all decisions`}

**🔄 Approval Process:**
${SYSTEM_KNOWLEDGE.policies.claims.approval_workflow}

**💡 Tips for ${userRole === 'employer' ? 'Efficient Management' : 'Faster Processing'}:**
${userRole === 'employee' ? `• Take clear, readable photos of receipts
• Provide detailed descriptions
• Submit regularly rather than bulk claims` : `• Set up automated notification system
• Use approval templates for common scenarios
• Maintain clear rejection reasons`}

🎯 **[Navigate to ${roleFeature ? roleFeature.description : 'Claim Management'}](${roleFeature ? roleFeature.path : '/claim-management'})**`;
}

/**
 * Generate attendance guide
 */
function generateAttendanceGuide() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleFeature = getFeatureForRole('attendance');
  
  return `⏰ **Comprehensive ${userRole === 'employer' ? 'Team Attendance Management' : 'Attendance'} Guide**

**📅 ${userRole === 'employer' ? 'Management Process' : 'Daily Process'}:**
${userRole === 'employee' ? `• **Clock In:** Mark arrival (before 9:15 AM)
• **Breaks:** ${SYSTEM_KNOWLEDGE.policies.attendance.lunch_break}
• **Clock Out:** Mark departure (after 6:00 PM)` : `• **Monitor Team:** Real-time attendance dashboard
• **Review Reports:** Daily, weekly, monthly summaries
• **Handle Corrections:** Approve time adjustments
• **Manage Schedules:** Set work patterns and shifts`}

**🕘 Working Schedule:**
• **Regular Hours:** ${SYSTEM_KNOWLEDGE.policies.attendance.working_hours}
• **Grace Period:** ${SYSTEM_KNOWLEDGE.policies.attendance.grace_period}
• **Overtime Rate:** ${SYSTEM_KNOWLEDGE.policies.attendance.overtime_rate}

**📊 Features Available:**
• **Real-time Tracking:** GPS location verification
• **${userRole === 'employer' ? 'Team Reports' : 'Monthly Reports'}:** Detailed attendance summaries
• **Overtime Calculation:** Automatic computation
• **Leave Integration:** Seamless with leave management
${userRole === 'employer' ? '• **Analytics Dashboard:** Attendance trends and insights' : ''}

**🚨 Important Notes:**
• Late arrivals beyond grace period are marked
• Overtime requires prior manager approval
• Weekend work needs special authorization
• Public holidays are automatically excluded

**💡 Best Practices:**
${userRole === 'employee' ? `• Mark attendance consistently
• Report technical issues immediately
• Keep backup records for important days` : `• Monitor attendance trends regularly
• Address recurring tardiness promptly
• Use attendance data for performance reviews`}

🎯 **[Navigate to ${roleFeature ? roleFeature.description : 'Attendance'}](${roleFeature ? roleFeature.path : '/attendance'})**`;
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
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleDisplay = userRole === 'employer' ? 'Manager/HR Dashboard' : 'Employee Dashboard';
  
  // Filter features based on role
  const availableFeatures = Object.entries(SYSTEM_KNOWLEDGE.features).filter(([key, feature]) => {
    if (key === 'recruitment' && userRole === 'employee') {
      return false;
    }
    return true;
  });
  
  return `📊 **SMEasyHR System Overview** - ${roleDisplay}

**📈 Current Statistics:**
• **Total Employees:** ${SYSTEM_KNOWLEDGE.systemData.totalEmployees}
• **Pending Leaves:** ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves} applications
• **Pending Claims:** ${SYSTEM_KNOWLEDGE.systemData.pendingClaims} submissions

**🏢 System Modules Available to You:**
${availableFeatures.map(([key, feature]) => {
  const roleFeature = getFeatureForRole(key);
  if (!roleFeature) return '';
  
  return `• **${roleFeature.description}**\n  ${roleFeature.capabilities.join(', ')}`;
}).filter(item => item).join('\n\n')}

**🎯 Your Current Location:** ${SYSTEM_KNOWLEDGE.systemData.currentSection}
**👤 Access Level:** ${roleDisplay}

**💡 What You Can Do:**
• Navigate between modules seamlessly
• Access role-appropriate features and reports
• Manage tasks efficiently within your permission level
• Get instant help and guidance

${userRole === 'employee' ? '\n📝 **Access Note:** Some administrative features like recruitment are restricted to management personnel.' : ''}

Need help with any specific module or task?`;
}

/**
 * Generate navigation guide
 */
function generateNavigationGuide() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  const roleDisplay = userRole === 'employer' ? 'Manager/HR' : 'Employee';
  
  // Filter features based on user role
  const availableFeatures = Object.entries(SYSTEM_KNOWLEDGE.features).filter(([key, feature]) => {
    if (key === 'recruitment' && userRole === 'employee') {
      return false; // Exclude recruitment for employees
    }
    return true;
  });
  
  return `🧭 **System Navigation Guide** (${roleDisplay} View)

**🏠 Main Sections Available to You:**
${availableFeatures.map(([key, feature]) => {
  const roleFeature = getFeatureForRole(key);
  if (!roleFeature) return ''; // Skip if no access
  
  return `• **${roleFeature.description}**\n  Path: ${roleFeature.path}\n  ${roleFeature.capabilities.slice(0, 3).join(', ')}`;
}).filter(item => item).join('\n\n')}

**⚡ Quick Navigation Tips:**
• Use sidebar menu for main sections
• Look for action buttons in each module
• Use breadcrumbs to track your location
• Search functionality available in most sections

**🎯 Current Location:** ${SYSTEM_KNOWLEDGE.systemData.currentSection}
**👤 Access Level:** ${roleDisplay}

${userRole === 'employee' ? '\n📝 **Note:** Recruitment features are available to managers and HR personnel only.' : ''}

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
- User role: ${SYSTEM_KNOWLEDGE.userRole || 'employee'}
- System stats: ${SYSTEM_KNOWLEDGE.systemData.totalEmployees} employees, ${SYSTEM_KNOWLEDGE.systemData.pendingLeaves} pending leaves, ${SYSTEM_KNOWLEDGE.systemData.pendingClaims} pending claims
- Available modules: Employee Management, Leave Management, Payroll, Attendance, Claim Management, Recruitment

User question: ${message}

Provide helpful, specific guidance about the SMEasyHR system appropriate for the user's role. Include step-by-step instructions when relevant.`;
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
    if (messagesContainer) {
      messagesContainer.appendChild(exploreContainer);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
  }
}

/**
 * Enhanced navigation buttons
 */
function addNavigationButtons() {
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  
  // Create navigation buttons based on role
  let navigationButtons = '';
  
  // Common buttons for all users
  navigationButtons += `<button onclick="navigateToSection('leave-management')" class="nav-btn">📅 ${userRole === 'employer' ? 'Manage' : 'Apply'} Leave</button>`;
  navigationButtons += `<button onclick="navigateToSection('payroll')" class="nav-btn">💰 ${userRole === 'employer' ? 'Payroll' : 'Payslip'}</button>`;
  navigationButtons += `<button onclick="navigateToSection('attendance')" class="nav-btn">⏰ ${userRole === 'employer' ? 'Team' : 'My'} Attendance</button>`;
  navigationButtons += `<button onclick="navigateToSection('claim-management')" class="nav-btn">🧾 ${userRole === 'employer' ? 'Approve' : 'Submit'} Claims</button>`;
  navigationButtons += `<button onclick="navigateToSection('employee-management')" class="nav-btn">👥 ${userRole === 'employer' ? 'Employees' : 'Profile'}</button>`;
  
  // Add recruitment button only for employers
  if (userRole === 'employer') {
    navigationButtons += `<button onclick="navigateToSection('recruitment')" class="nav-btn">🎯 Recruitment</button>`;
  }
  
  const navContainer = document.createElement('div');
  navContainer.className = 'navigation-buttons';
  navContainer.innerHTML = `
    <div class="nav-title">🚀 Quick System Navigation (${userRole === 'employer' ? 'Manager' : 'Employee'} View):</div>
    <div class="nav-buttons">
      ${navigationButtons}
    </div>
  `;
  
  const messagesContainer = document.getElementById('chatbotMessages');
  if (messagesContainer) {
    messagesContainer.appendChild(navContainer);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

/**
 * Navigate to system section
 */
function navigateToSection(section) {
  const roleFeature = getFeatureForRole(section);
  
  if (!roleFeature) {
    const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
    
    if (section === 'recruitment' && userRole === 'employee') {
      addMessage('🚫 **Access Restricted**: Recruitment features are only available to managers and HR personnel. You can access your employee features like leave applications, payslips, attendance, and expense claims.', 'ai');
      return;
    }
    
    addMessage('❌ **Section Not Found**: The requested section is not available or accessible with your current permissions.', 'ai');
    return;
  }
  
  const userRole = SYSTEM_KNOWLEDGE.userRole || detectUserRole();
  addMessage(`🚀 Navigating to ${roleFeature.description} (${userRole === 'employer' ? 'Management' : 'Employee'} view)...`, 'ai');
  setTimeout(() => {
    window.location.href = roleFeature.path;
  }, 1000);
}

// Enhanced UI Functions
function addMessage(text, sender) {
  const messagesContainer = document.getElementById('chatbotMessages');
  if (!messagesContainer) {
    console.error('❌ Messages container not found');
    return;
  }
  
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

// Safe UI functions
function toggleChatbot() {
  try {
    const chatbotWindow = document.getElementById('chatbotWindow');
    if (!chatbotWindow) {
      console.error('❌ Chatbot window not found');
      return;
    }
    
    if (chatbotWindow.style.display === 'none' || chatbotWindow.style.display === '') {
      chatbotWindow.style.display = 'flex';
      
      const input = document.getElementById('chatbotInput');
      if (input) {
        input.focus();
      }
      
      if (!chatbotInitialized) {
        initializeChatbot();
      }
    } else {
      chatbotWindow.style.display = 'none';
    }
  } catch (error) {
    console.error('❌ Error toggling chatbot:', error);
  }
}

/**
 * SAFE: Handle key press events with error handling
 */
function handleKeyPress(event) {
  try {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  } catch (error) {
    console.error('❌ Error in handleKeyPress:', error);
  }
}

function autoResize(textarea) {
  try {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
  } catch (error) {
    console.error('❌ Error resizing textarea:', error);
  }
}

function sendQuickMessage(message) {
  try {
    const input = document.getElementById('chatbotInput');
    if (input) {
      input.value = message;
      sendMessage();
    }
  } catch (error) {
    console.error('❌ Error sending quick message:', error);
  }
}

// Add test functions to window for debugging
window.testChatbot = testChatbot;
window.debugChatbot = debugChatbot;

// Ready state
document.addEventListener('DOMContentLoaded', function() {
  console.log('🌟 Universal SMEasyHR AI Assistant ready to initialize when needed...');
});