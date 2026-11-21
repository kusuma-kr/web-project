
let transactions = [];
let expenseChart, incomeChart;

const money = n => "₹" + Number(n).toLocaleString("en-IN", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});


async function request(url, options = {}) {
    const headers = new Headers(options.headers || {});

    const token = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    if (options.method === "POST" && token) {
        headers.set("X-CSRF-Token", token);
    }

    const response = await fetch(url, {
        ...options,
        headers
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || "Request failed");
    }

    return data;
}


async function loadData() {
    try {
        transactions = await request("api.php?action=list");
        render();
    } catch (error) {
        alert(error.message);
    }
}

function render() {
    const income = transactions
        .filter(t => t.type === "income")
        .reduce((s, t) => s + Number(t.amount), 0);

    const expense = transactions
        .filter(t => t.type === "expense")
        .reduce((s, t) => s + Number(t.amount), 0);

    document.getElementById("balance").textContent = money(income - expense);
    document.getElementById("income").textContent = money(income);
    document.getElementById("expense").textContent = money(expense);
    document.getElementById("count").textContent = transactions.length;

    const search = document.getElementById("search").value.toLowerCase();

    const filtered = transactions.filter(t =>
        [t.title, t.category, t.notes || ""]
            .some(v => v.toLowerCase().includes(search))
    );

    document.getElementById("transactionList").innerHTML =
        filtered.map(t => `
        <tr>
            <td>${escapeHTML(t.title)}</td>
            <td>${escapeHTML(t.category)}</td>
            <td>${escapeHTML(t.transaction_date)}</td>
            <td class="${t.type}">${t.type}</td>
            <td class="${t.type}">
                ${t.type === "income" ? "+" : "-"}${money(t.amount)}
            </td>
            <td>
                <button class="action-btn"
                    onclick="editTransaction(${Number(t.id)})">Edit</button>
                <button class="action-btn delete-btn"
                    onclick="deleteTransaction(${Number(t.id)})">Delete</button>
            </td>
        </tr>
        `).join("");

    updateCharts();
}

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, c => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[c]);
}

function updateCharts() {
    const categories = {};

    transactions.filter(t => t.type === "expense")
        .forEach(t => {
            categories[t.category] =
                (categories[t.category] || 0) + Number(t.amount);
        });

    if (expenseChart) expenseChart.destroy();
    if (incomeChart) incomeChart.destroy();

    expenseChart = new Chart(document.getElementById("expenseChart"), {
        type: "doughnut",
        data: {
            labels: Object.keys(categories),
            datasets: [{
                data: Object.values(categories),
                backgroundColor: ["#16865a", "#f1b94c",
                    "#e36a6a", "#6a9de3", "#9b79d6"]
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    const income = transactions
        .filter(t => t.type === "income")
        .reduce((s, t) => s + Number(t.amount), 0);

    const expense = transactions
        .filter(t => t.type === "expense")
        .reduce((s, t) => s + Number(t.amount), 0);

    incomeChart = new Chart(document.getElementById("incomeChart"), {
        type: "bar",
        data: {
            labels: ["Income", "Expenses"],
            datasets: [{
                label: "Amount",
                data: [income, expense],
                backgroundColor: ["#16865a", "#e36a6a"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true } }
        }
    });
}

function openForm() {
    document.getElementById("transactionForm").reset();
    document.getElementById("id").value = "";
    document.getElementById("formTitle").textContent = "Add Transaction";
    document.getElementById("date").value =
        new Date().toISOString().slice(0, 10);
    document.getElementById("modal").style.display = "flex";
}

function closeForm() {
    document.getElementById("modal").style.display = "none";
}

function editTransaction(id) {
    const t = transactions.find(x => Number(x.id) === id);
    if (!t) return;

    document.getElementById("id").value = t.id;
    document.getElementById("type").value = t.type;
    document.getElementById("title").value = t.title;
    document.getElementById("category").value = t.category;
    document.getElementById("amount").value = t.amount;
    document.getElementById("date").value = t.transaction_date;
    document.getElementById("notes").value = t.notes || "";
    document.getElementById("formTitle").textContent = "Edit Transaction";
    document.getElementById("modal").style.display = "flex";
}

document.getElementById("transactionForm").addEventListener("submit",
async function(e) {
    e.preventDefault();

    const id = document.getElementById("id").value;

    const data = {
        id: id,
        type: document.getElementById("type").value,
        title: document.getElementById("title").value.trim(),
        category: document.getElementById("category").value.trim(),
        amount: Number(document.getElementById("amount").value),
        transaction_date: document.getElementById("date").value,
        notes: document.getElementById("notes").value.trim()
    };

    try {
        await request("api.php?action=" + (id ? "update" : "add"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        closeForm();
        await loadData();
    } catch (error) {
        alert(error.message);
    }
});

async function deleteTransaction(id) {
    if (!confirm("Are you sure you want to delete this transaction?")) return;

    try {
        await request("api.php?action=delete", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });
        await loadData();
    } catch (error) {
        alert(error.message);
    }
}

loadData();
