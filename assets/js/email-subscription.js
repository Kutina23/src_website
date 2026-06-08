/**
 * Email Subscription Manager
 * Handles newsletter subscription/unsubscription in footer and across the site
 */

class EmailSubscriptionManager {
    constructor() {
        this.apiEndpoint = 'api/email-subscribe.php';
        this.init();
    }

    init() {
        // Initialize subscription form handlers
        const subscribeForm = document.getElementById('subscribe-form');
        if (subscribeForm) {
            subscribeForm.addEventListener('submit', (e) => this.handleSubscribe(e));
        }

        // Handle unsubscribe link if present
        const unsubscribeLinks = document.querySelectorAll('[data-unsubscribe-token]');
        unsubscribeLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleQuickUnsubscribe(e, link));
        });
    }

    /**
     * Handle subscription form submission
     */
    async handleSubscribe(event) {
        event.preventDefault();

        const form = event.target;
        const email = document.getElementById('subscribe-email').value.trim();
        const fullName = document.getElementById('subscribe-name').value.trim();
        const resultDiv = document.getElementById('subscribe-result');

        // Validate email
        if (!this.isValidEmail(email)) {
            this.showResult(resultDiv, 'Please enter a valid email address', 'error');
            return;
        }

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'subscribe',
                    email: email,
                    full_name: fullName || null
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showResult(resultDiv, data.message || 'Successfully subscribed!', 'success');
                form.reset();
                
                // Log subscription event
                this.logEvent('newsletter_subscribe', { email });
            } else {
                this.showResult(resultDiv, data.message || 'Subscription failed', 'error');
            }
        } catch (error) {
            console.error('Subscription error:', error);
            this.showResult(resultDiv, 'An error occurred. Please try again.', 'error');
        }
    }

    /**
     * Handle quick unsubscribe from email link
     */
    async handleQuickUnsubscribe(event, element) {
        event.preventDefault();

        const token = element.getAttribute('data-unsubscribe-token');
        if (!token) return;

        if (!confirm('Are you sure you want to unsubscribe from our announcements?')) {
            return;
        }

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'unsubscribe',
                    token: token
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('You have been unsubscribed from announcements.');
                this.logEvent('newsletter_unsubscribe', { token });
            } else {
                alert('Error: ' + (data.message || 'Could not unsubscribe'));
            }
        } catch (error) {
            console.error('Unsubscribe error:', error);
            alert('An error occurred. Please try again.');
        }
    }

    /**
     * Show result message to user
     */
    showResult(element, message, type = 'info') {
        if (!element) return;

        element.textContent = message;
        element.className = `footer-result footer-result-${type}`;
        element.style.display = 'block';

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Log events for analytics
     */
    logEvent(eventName, eventData = {}) {
        // Send to analytics if available
        if (window.gtag) {
            window.gtag('event', eventName, eventData);
        }
    }

    /**
     * Unsubscribe from URL token (used in email links)
     */
    static unsubscribeFromToken(token) {
        const manager = new EmailSubscriptionManager();
        const fakeElement = { getAttribute: () => token };
        const fakeEvent = { preventDefault: () => {} };
        manager.handleQuickUnsubscribe(fakeEvent, fakeElement);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new EmailSubscriptionManager();
});
