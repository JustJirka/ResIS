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


let currentFloor = 1;

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
            <div class="kv">Ticket Type: <strong>${ticket.type || 'seating'}</strong></div>
        </div>
        ${ticket.current_table
            ? `<button onclick="releaseTable()" class="danger">Release</button>`
            : ""
        }
    </div>
  `;
    app.appendChild(info);

    // 2. Map View
    const mapSection = document.createElement("div");
    mapSection.className = "row align-start";
    mapSection.style.marginTop = "24px";
    mapSection.style.gap = "20px";

    // Controls Side
    const controls = document.createElement("div");
    controls.style.minWidth = "150px";

    const mapTitle = document.createElement("h3");
    mapTitle.textContent = "Floor Plan";
    mapTitle.style.marginBottom = "10px";
    controls.appendChild(mapTitle);

    const floorLabel = document.createElement("p");
    floorLabel.className = "muted";
    floorLabel.textContent = "Select floor:";
    controls.appendChild(floorLabel);

    const btnGroup = document.createElement("div");
    btnGroup.className = "stack";
    btnGroup.style.gap = "8px";

    [1, 2].forEach(f => {
        const btn = document.createElement("button");
        btn.textContent = f === 1 ? "Ground Floor" : "1st Floor";
        // Simple styling toggle
        if (currentFloor === f) {
            btn.style.backgroundColor = "var(--primary, #333)";
            btn.style.color = "#fff";
        } else {
            btn.className = "outline";
            btn.onclick = () => {
                currentFloor = f;
                render(data);
            };
        }
        btn.style.width = "100%";
        btnGroup.appendChild(btn);
    });
    controls.appendChild(btnGroup);
    mapSection.appendChild(controls);

    // Map Container
    const mapContainer = document.createElement("div");
    mapContainer.className = "map-container";
    mapContainer.style.flex = "1";

    // Layer 1: Ground Floor (Always loaded, visibility depends on mode)
    const layer1 = document.createElement("img");
    layer1.src = "public/assets/patro1.png";
    layer1.className = `map-layer ${currentFloor === 1 ? 'active' : 'active'}`;
    mapContainer.appendChild(layer1);

    // Layer 2: 1st Floor
    const layer2 = document.createElement("img");
    layer2.src = "public/assets/patro2.png";
    layer2.className = `map-layer ${currentFloor === 2 ? 'active' : 'hidden'}`;
    mapContainer.appendChild(layer2);

    tables.forEach((t) => {
        // Only interactive elements for CURRENT floor
        if (t.floor !== currentFloor) return;

        // Filter by Type logic
        const ticketType = ticket.type || 'seating';
        const tableType = t.type || 'seating';
        if (tableType !== ticketType) return;

        // Determine status
        const isCurrent = ticket.current_table && ticket.current_table.table_id === t.id;
        const isFull = t.remaining < 1;
        const isPartial = t.remaining < t.capacity && t.remaining > 0;
        const isEmpty = t.remaining === t.capacity;

        let statusClass = "status-empty";
        if (isCurrent) statusClass = "status-current";
        else if (isFull) statusClass = "status-full";
        else if (isPartial) statusClass = "status-partial";

        // Map Table Container
        const dot = document.createElement("div");
        dot.className = `map-table ${statusClass} type-${tableType}`;
        dot.style.left = t.position_x + "%";
        dot.style.top = t.position_y + "%";

        // Center Label
        const center = document.createElement("div");
        center.className = "table-center";
        center.textContent = t.label;
        dot.appendChild(center);

        // Chairs - ONLY for seating tables
        if (tableType === 'seating') {
            const radius = 24; // distance from center
            const startAngle = -90; // start at top
            const angleStep = 360 / t.capacity;

            const occupiedCount = t.capacity - t.remaining;

            for (let i = 0; i < t.capacity; i++) {
                const angleDeg = startAngle + (i * angleStep);
                const angleRad = angleDeg * (Math.PI / 180);

                const cx = Math.cos(angleRad) * radius;
                const cy = Math.sin(angleRad) * radius;

                const chair = document.createElement("div");
                const isChairOccupied = i < occupiedCount;
                chair.className = `chair ${isChairOccupied ? 'occupied' : 'free'}`;

                chair.style.transform = `translate(${cx}px, ${cy}px)`;

                dot.appendChild(chair);
            }
        } else {
            dot.classList.add('standing-table');
        }

        if (isCurrent) {
            dot.title = "Your Table";
        } else if (isFull) {
            dot.title = "Occupied";
        } else {
            dot.onclick = () => {
                if (confirm(`Assign to table ${t.label}?`)) {
                    assignTable(t.id);
                }
            };
            dot.title = `Select ${t.label} (Remaining: ${t.remaining})`;
        }

        mapContainer.appendChild(dot);
    });
    mapSection.appendChild(mapContainer);

    app.appendChild(mapSection);


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
