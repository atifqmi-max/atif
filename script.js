document.addEventListener('DOMContentLoaded', () => {
    const categoryList = document.getElementById('category-list');
    const generatorPanel = document.getElementById('generator-panel');
    const selectedCatEl = document.getElementById('selected-category');
    const stockInfo = document.getElementById('stock-info');
    const generateBtn = document.getElementById('generate-btn');
    const resultArea = document.getElementById('result-area');

    let currentCategory = null;
    let stockCount = 0;

    // Load all categories & stock
    async function loadCategories() {
        const res = await fetch('api/get_stock.php');
        const data = await res.json();
        renderCategories(data);
    }

    function renderCategories(stockData) {
        categoryList.innerHTML = '';
        for (const [cat, count] of Object.entries(stockData)) {
            const card = document.createElement('div');
            card.className = 'category-card';
            card.dataset.category = cat;
            card.innerHTML = `
                <h3>${cat}</h3>
                <div class="stock-badge">
                    <span>${count}</span> available
                </div>
            `;
            card.addEventListener('click', () => selectCategory(cat, count));
            categoryList.appendChild(card);
        }
    }

    function selectCategory(cat, count) {
        currentCategory = cat;
        stockCount = count;
        selectedCatEl.textContent = cat;
        stockInfo.textContent = `📦 Stock: ${count} account${count !== 1 ? 's' : ''}`;
        generatorPanel.style.display = 'block';
        resultArea.innerHTML = ''; // clear previous result
        window.scrollTo({ top: generatorPanel.offsetTop - 20, behavior: 'smooth' });
    }

    generateBtn.addEventListener('click', async () => {
        if (!currentCategory) return;

        generateBtn.disabled = true;
        generateBtn.textContent = '⏳ Generating...';

        const formData = new FormData();
        formData.append('category', currentCategory);

        try {
            const res = await fetch('api/generate.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (result.success) {
                // Show account with copy button
                resultArea.innerHTML = `
                    <div class="account-box">
                        <span class="account-text">${result.account}</span>
                        <button class="copy-btn" data-account="${result.account}">📋 Copy</button>
                    </div>
                `;
                // Attach copy event
                document.querySelector('.copy-btn').addEventListener('click', (e) => {
                    const account = e.target.dataset.account;
                    navigator.clipboard.writeText(account).then(() => {
                        e.target.textContent = '✅ Copied!';
                        setTimeout(() => (e.target.textContent = '📋 Copy'), 1500);
                    });
                });

                // Update stock count visually
                stockCount = result.new_stock;
                stockInfo.textContent = `📦 Stock: ${stockCount} account${stockCount !== 1 ? 's' : ''}`;
            } else {
                // Error message (cooldown / out of stock)
                resultArea.innerHTML = `<div class="error-message">⚠️ ${result.message}</div>`;
            }
        } catch (err) {
            resultArea.innerHTML = `<div class="error-message">Network error. Try again.</div>`;
        } finally {
            generateBtn.disabled = false;
            generateBtn.textContent = '🚀 Generate Now';
        }
    });

    loadCategories();

    // Auto-refresh stock every 30 seconds (optional)
    setInterval(() => {
        if (!currentCategory) return;
        fetch('api/get_stock.php')
            .then(res => res.json())
            .then(data => {
                if (data[currentCategory] !== undefined) {
                    stockCount = data[currentCategory];
                    stockInfo.textContent = `📦 Stock: ${stockCount} account${stockCount !== 1 ? 's' : ''}`;
                }
            });
    }, 30000);
});
