/* public/assets/app.js */

const BASE = (window.APP_BASE || "").replace(/\/$/, "");

function urlJoin(path) {
    return (BASE + path).replace(/([^:]\/)\/+/g, "$1");
}

async function fetchTicket() {
    const code = document.getElementById("app").dataset.code;
    const res = await fetch(urlJoin(`/api/ticket.php?code=${code}`));
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw { status: res.status, data };
    }
    return res.json();
}

async function assignTable(tableId) {
    const code = document.getElementById("app").dataset.code;
    const res = await fetch(urlJoin("/api/assign.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code, table_id: tableId }),
    });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        alert(data.error || "Failed to assign table");
        return;
    }
    load(); // Reload
}

async function releaseTable() {
    const code = document.getElementById("app").dataset.code;
    if (!confirm("Release current table?")) return;
    const res = await fetch(urlJoin("/api/release.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code }),
    });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        alert(data.error || "Failed to release table");
        return;
    }
    load();
}

function render(data) {
    const app = document.getElementById("app");
    app.innerHTML = "";

    const ticket = data.ticket;
    const tables = data.available_tables;

    // 1. Ticket Info
    const info = document.createElement("div");
    info.className = "card";
    let currentHtml = `<span class="muted">None</span>`;
    if (ticket.current_table) {
        currentHtml = `<strong>${ticket.current_table.label}</strong> (Capacity: ${ticket.current_table.capacity})`;
    }

    info.innerHTML = `
    <div class="row space">
        <div>
            <h2>Reservation ${ticket.code}</h2>
            <div class="kv">Current Table: ${currentHtml}</div>
        </div>
        ${ticket.current_table
            ? `<button onclick="releaseTable()" class="danger">Release</button>`
            : ""
        }
    </div>
  `;
    app.appendChild(info);

    // 2. Map View
    const mapTitle = document.createElement("h3");
    mapTitle.textContent = "Floor Plan";
    mapTitle.style.marginTop = "24px";
    app.appendChild(mapTitle);

    const mapContainer = document.createElement("div");
    mapContainer.className = "map-container";

    const img = document.createElement("img");
    img.src = "public/assets/patro1.png";
    img.alt = "Floor Plan";
    mapContainer.appendChild(img);

    tables.forEach((t) => {
        // Determine status
        const isCurrent = ticket.current_table && ticket.current_table.table_id === t.id;
        const isFull = t.remaining < 1;

        // Map Dot
        const dot = document.createElement("div");
        dot.className = `map-table ${isCurrent ? 'current' : ''} ${isFull && !isCurrent ? 'full' : ''}`;
        dot.textContent = t.label;

        dot.style.left = t.position_x + "%";
        dot.style.top = t.position_y + "%";

        if (isCurrent) {
            dot.title = "Your Table";
        } else if (isFull) {
            dot.title = "Occupied";
        } else {
            dot.onclick = () => assignTable(t.id);
            dot.title = `Select ${t.label} (Remaining: ${t.remaining})`;
        }

        mapContainer.appendChild(dot);
    });
    app.appendChild(mapContainer);

    // 3. List view
    const listTitle = document.createElement("h3");
    listTitle.textContent = "Table List";
    listTitle.style.marginTop = "24px";
    app.appendChild(listTitle);

    const grid = document.createElement("div");
    grid.className = "grid";

    tables.forEach((t) => {
        const isCurrent = ticket.current_table && ticket.current_table.table_id === t.id;
        const isFull = t.remaining < 1;

        const item = document.createElement("div");
        item.className = "card";
        item.style.textAlign = "center";

        // Visual cue for status
        let statusHtml = "";
        if (isCurrent) {
            statusHtml = `<div style="color:var(--green); font-weight:bold;">Selected</div>`;
        } else if (isFull) {
            statusHtml = `<div style="color:var(--muted);">Full</div>`;
        } else {
            statusHtml = `<button onclick="assignTable(${t.id})">Select</button>`;
        }

        item.innerHTML = `
        <div style="font-size: 24px; font-weight: bold; margin-bottom: 8px;">${t.label}</div>
        <div class="muted" style="margin-bottom: 12px;">Free: ${t.remaining} / ${t.capacity}</div>
        ${statusHtml}
    `;
        grid.appendChild(item);
    });
    app.appendChild(grid);
}

async function load() {
    const app = document.getElementById("app");
    try {
        const data = await fetchTicket();
        render(data);
    } catch (e) {
        app.innerHTML = `<div class="card" style="color:red">
       <h3>Error loading ticket</h3>
       <p>${e.data?.error || e.message || "Unknown error"}</p>
    </div>`;
    }
}

window.addEventListener("DOMContentLoaded", load);
window.assignTable = assignTable;
window.releaseTable = releaseTable;
