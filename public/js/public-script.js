document.addEventListener('DOMContentLoaded', function() {
    const chatRoot = document.getElementById('easyai-chat-root');
    if (!chatRoot) return;

    // Check if user has already used their allowed questions
    const questionsAsked = parseInt(localStorage.getItem('easyai_chat_questions') || '0');
    const allowedQuestions = easyAIChat.allowedQuestions;
    
    if (questionsAsked >= allowedQuestions) {
        displayAppPromotion(chatRoot);
        return;
    }

    // Initialize chat based on display type
    if (easyAIChat.displayType === 'popup') {
        initializePopupChat(chatRoot);
    } else {
        initializeInlineChat(chatRoot);
    }
});

function initializeInlineChat(chatRoot) {
    // Create form and examples HTML outside the main template literal
    const formHtml = `
        <form id="ec-chat-form" class="ec-chat-form">
            <div class="ec-input-group">
                <textarea
                    id="ec-chat-input"
                    placeholder="Type your question..."
                    rows="1"
                    class="ec-auto-expand"
                ></textarea>
                <button type="submit" id="ec-submit-button" class="ec-send-button">
                    Send
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </form>
    `;

    const exampleHtml = (easyAIChat.exampleQuestions && easyAIChat.exampleQuestions.length > 0) 
        ? `
            <div class="ec-examples">
                <h3>Example questions</h3>
                <div class="ec-example-cards">
                    ${easyAIChat.exampleQuestions.map(q => `
                        <div class="ec-example-card">${escapeHtml(q)}</div>
                    `).join('')}
                </div>
            </div>
        ` 
        : '';

    // Now build the main chat HTML using the variables above
    chatRoot.innerHTML = `
        <div class="ec-chat-container" style="--theme-color: ${easyAIChat.themeColor}">
            <div class="ec-chat-header">
                <h2>${easyAIChat.chatTitle || 'EasyAI Chat'}</h2>
                <p>${easyAIChat.chatSubtitle || 'Ask me anything!'}</p>
            </div>
            <div id="ec-response-area"></div>
            ${formHtml}
            ${exampleHtml}
        </div>
    `;

    setupChatEventHandlers();
    setupAutoExpandTextarea(); 
}

function initializePopupChat(chatRoot) {
    // Create popup chat interface
    chatRoot.innerHTML = `
        <div class="ec-chat-popup ${easyAIChat.position}" style="--theme-color: ${easyAIChat.themeColor}">
            <div class="ec-chat-trigger">
                ${getIconSvg(easyAIChat.buttonIcon)}
            </div>
            <div class="ec-chat-popup-content">
                <div class="ec-chat-header">
                    <h2>${easyAIChat.chatTitle || 'EasyAI Chat'}</h2>
                    <p>${easyAIChat.chatSubtitle || 'Ask me anything!'}</p>
                    <button class="ec-close-button">&times;</button>
                </div>

                <div id="ec-response-area"></div>

                <form id="ec-chat-form" class="ec-chat-form">
                <div class="ec-input-group">
                    <input
                        type="text"
                        id="ec-chat-input"
                        placeholder="Type your question..."
                    />
                    <button type="submit" id="ec-submit-button" class="ec-send-button">
                        Send
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                </form>

                <div class="ec-examples">
                    <h3>Example questions</h3>
                    <div class="ec-example-cards">
                        <div class="ec-example-card">What is the best temperature for raising tilapia?</div>
                        <div class="ec-example-card">How do I improve water quality in my fish tank?</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    const trigger = chatRoot.querySelector('.ec-chat-trigger');
    const popup = chatRoot.querySelector('.ec-chat-popup-content');
    const closeButton = chatRoot.querySelector('.ec-close-button');
    
    // Show/hide popup
    trigger.addEventListener('click', () => {
        popup.style.display = 'block';
    });
    
    closeButton.addEventListener('click', () => {
        popup.style.display = 'none';
    });

    setupChatEventHandlers();
}

function setupChatEventHandlers() {
    const form = document.getElementById('ec-chat-form');
    const input = document.getElementById('ec-chat-input');
    const submitButton = document.getElementById('ec-submit-button');
    const responseArea = document.getElementById('ec-response-area');
    const exampleCards = document.querySelectorAll('.ec-example-card');
    
    // Example question click handler
    exampleCards.forEach(card => {
        card.addEventListener('click', () => {
            input.value = card.textContent;
            input.focus();
        });
    });
    
    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!input.value.trim()) return;

        // Disable input during processing
        input.disabled = true;
        submitButton.disabled = true;
        submitButton.innerHTML = '<div class="ec-spinner"></div>';
        
        const message = input.value;
        input.value = '';

        // Show user message
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'ec-user-message';
        userMessageDiv.innerHTML = `<p>${escapeHtml(message)}</p>`;
        responseArea.appendChild(userMessageDiv);
        
        try {
            const response = await fetch(easyAIChat.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': easyAIChat.nonce
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            
            // Show AI response
            const aiMessageDiv = document.createElement('div');
            aiMessageDiv.className = 'ec-ai-message';
            aiMessageDiv.innerHTML = `<p>${data.response}</p>`;
            responseArea.appendChild(aiMessageDiv);
            
            // Increment questions count
            const questionsAsked = parseInt(localStorage.getItem('easyai_chat_questions') || '0') + 1;
            localStorage.setItem('easyai_chat_questions', questionsAsked);
            
            // Check if reached limit
            if (questionsAsked >= easyAIChat.allowedQuestions) {
                form.style.display = 'none';
                document.querySelector('.ec-examples').style.display = 'none';
                
                // Show custom limit message
                const promoDiv = document.createElement('div');
                promoDiv.className = 'ec-promotion';
                promoDiv.innerHTML = `
                    <p>${easyAIChat.limitMessage}</p>
                    ${easyAIChat.limitButtonUrl ? `
                        <a href="${easyAIChat.limitButtonUrl}" class="ec-button" target="_blank">
                            ${easyAIChat.limitButtonText}
                        </a>
                    ` : ''}
                `;
                responseArea.appendChild(promoDiv);
            }
            
        } catch (error) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'ec-error';
            errorDiv.textContent = 'Sorry, I encountered an error. Please try again later.';
            responseArea.appendChild(errorDiv);
        } finally {
            // Re-enable input
            input.disabled = false;
            submitButton.disabled = false;
            submitButton.innerHTML = `
                Send
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            `;
            
            // Scroll to bottom
            responseArea.scrollTop = responseArea.scrollHeight;
        }
    });
}

function displayAppPromotion(chatRoot) {
    chatRoot.innerHTML = `
    <div class="ec-chat-container" style="--theme-color: ${easyAIChat.themeColor}">
        <div class="ec-chat-header">
            <h2>${easyAIChat.chatTitle || 'EasyAI Chat'}</h2>
            <p>${easyAIChat.chatSubtitle || 'Ask me anything!'}</p>
        </div>

        <div id="ec-response-area"></div>

        ${formHtml}
        ${exampleHtml}
    </div>
    `;
}

function getIconSvg(icon) {
    switch(icon) {
        case 'chat':
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/></svg>';
        case 'message':
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';
        case 'help':
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>';
        default:
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/></svg>';
    }
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

const setupAutoExpandTextarea = () => {
    const textarea = document.getElementById('ec-chat-input');
    if (!textarea) return;
    
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Reset height when form is submitted
    document.getElementById('ec-chat-form').addEventListener('submit', function() {
        setTimeout(() => {
            textarea.style.height = 'auto';
        }, 0);
    });
};