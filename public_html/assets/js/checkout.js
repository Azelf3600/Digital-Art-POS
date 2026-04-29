// public_html/assets/js/checkout.js

document.addEventListener('DOMContentLoaded', () => {

    const loadingEl = document.getElementById('paypal-loading');
    const errorEl   = document.getElementById('paypal-error');
    const container = document.getElementById('paypal-button-container');

    // Wait for PayPal SDK to load
    function initPayPal() {
        if (typeof paypal === 'undefined') {
            // Retry after 1s if SDK hasn't loaded yet
            setTimeout(initPayPal, 1000);
            return;
        }

        // Hide loading, show button
        if (loadingEl) loadingEl.style.display = 'none';

        paypal.Buttons({

            style: {
                layout:  'vertical',
                color:   'gold',
                shape:   'rect',
                label:   'pay',
                height:  45,
            },

            // Step 1 — Create order in our DB AND in PayPal, return PayPal's order ID
            createOrder: async () => {
                const notes = document.getElementById('order-notes')?.value || '';

                try {
                    const res  = await fetch('actions/checkout.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `notes=${encodeURIComponent(notes)}`,
                    });
                    const data = await res.json();

                    if (!data.success) {
                        showError(data.error || 'Could not create order.');
                        return null;
                    }

                    // Store our internal DB order ID for use in onApprove
                    window._starflowOrderId = data.order_id;

                    // Return PayPal's order ID (NOT our DB order ID)
                    return data.paypal_order_id;

                } catch (err) {
                    showError('Something went wrong. Please try again.');
                    console.error('createOrder error:', err);
                    return null;
                }
            },

            // Step 2 — PayPal approved, now verify + capture on our server
            onApprove: async (ppData) => {
                showProcessing();

                try {
                    const res  = await fetch('actions/payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `paypal_order_id=${ppData.orderID}&order_id=${window._starflowOrderId}`,
                    });
                    const data = await res.json();

                    if (data.success) {
                        // Redirect to order tracking
                        window.location.href = data.redirect;
                    } else {
                        showError(data.error || 'Payment could not be completed.');
                    }

                } catch (err) {
                    showError('Payment verification failed. Please contact support.');
                    console.error('onApprove error:', err);
                }
            },

            onCancel: () => {
                if (loadingEl) loadingEl.style.display = 'none';
                console.log('PayPal payment cancelled by user.');
            },

            onError: (err) => {
                showError('PayPal encountered an error. Please try again.');
                console.error('PayPal error:', err);
            },

        }).render('#paypal-button-container');
    }

    function showError(msg) {
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) {
            errorEl.textContent   = msg;
            errorEl.style.display = 'block';
        }
    }

    function showProcessing() {
        if (container) {
            container.innerHTML = `
                <div style="text-align:center;padding:1.5rem;">
                    <div style="width:32px;height:32px;border:3px solid rgba(255,255,255,0.1);border-top-color:#c9a96e;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem;"></div>
                    <p style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-dim);letter-spacing:0.05em;">
                        Processing payment...
                    </p>
                </div>
            `;
        }
    }

    // Start init
    initPayPal();

});