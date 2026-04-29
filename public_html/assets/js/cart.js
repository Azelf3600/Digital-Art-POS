// public_html/assets/js/cart.js

document.addEventListener('DOMContentLoaded', () => {

    // ── Remove Item ──
    document.querySelectorAll('.cart-item__remove').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cartId  = btn.dataset.cartId;
            const cartItem = document.getElementById('cart-item-' + cartId);

            // Optimistic fade out
            cartItem.style.opacity = '0.4';
            cartItem.style.pointerEvents = 'none';

            try {
                const res  = await fetch('actions/cart-remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `cart_id=${cartId}`,
                });
                const data = await res.json();

                if (data.success) {
                    // Remove row with animation
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.height     = cartItem.offsetHeight + 'px';
                    cartItem.style.overflow   = 'hidden';

                    requestAnimationFrame(() => {
                        cartItem.style.height  = '0';
                        cartItem.style.padding = '0';
                        cartItem.style.opacity = '0';
                    });

                    setTimeout(() => {
                        cartItem.remove();
                        updateTotal(data.total_formatted, data.count);
                    }, 300);

                } else {
                    // Revert
                    cartItem.style.opacity      = '1';
                    cartItem.style.pointerEvents = '';
                }

            } catch (err) {
                cartItem.style.opacity      = '1';
                cartItem.style.pointerEvents = '';
                console.error('Remove error:', err);
            }
        });
    });

    // ── Update total display ──
    function updateTotal(totalFormatted, count) {
        const totalEl = document.getElementById('cart-total');
        if (totalEl) totalEl.textContent = totalFormatted;

        // If cart is now empty, reload to show empty state
        if (count === 0) {
            setTimeout(() => window.location.reload(), 200);
        }
    }

});