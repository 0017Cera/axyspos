document.addEventListener('DOMContentLoaded', function() {
    let itemCount = 1;
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemButton = document.getElementById('addItem');
    const form = document.getElementById('poForm');

    function updateItemTotals() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const itemTotal = quantity * unitPrice;
            row.querySelector('.item-total').value = itemTotal.toFixed(2);
            total += itemTotal;
        });
        document.getElementById('totalAmount').textContent = `$${total.toFixed(2)}`;
    }

    function addItemRow() {
        const newRow = document.createElement('div');
        newRow.className = 'row mb-3 item-row';
        newRow.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Item Name</label>
                <input type="text" class="form-control" name="items[${itemCount}][name]" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control quantity" name="items[${itemCount}][quantity]" required min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">Unit Price</label>
                <input type="number" class="form-control unit-price" name="items[${itemCount}][unit_price]" required min="0" step="0.01">
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control item-total" readonly>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        itemsContainer.appendChild(newRow);
        itemCount++;

        // Show remove buttons if there's more than one item
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.style.display = document.querySelectorAll('.item-row').length > 1 ? 'block' : 'none';
        });
    }

    addItemButton.addEventListener('click', addItemRow);

    itemsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const row = e.target.closest('.item-row');
            row.remove();
            updateItemTotals();
            // Show/hide remove buttons
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.style.display = document.querySelectorAll('.item-row').length > 1 ? 'block' : 'none';
            });
        }
    });

    itemsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price')) {
            updateItemTotals();
        }
    });
}); 