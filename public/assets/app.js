// public/assets/app.js

const BASE = (window.APP_BASE || "").replace(/\/+$/, "");
const urlJoin = (path) => `${BASE}/${path}`.replace(/\/{2,}/g, "/");

async function apiGet(url) {
  const res = await fetch(urlJoin(url), { headers: { Accept: "application/json" }, cache: "no-store" });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw { status: res.status, data, url: urlJoin(url) };
  return data;
}

async function apiPost(url, body) {
  const res = await fetch(urlJoin(url), {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(body),
    cache: "no-store",
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw { status: res.status, data, url: urlJoin(url) };
  return data;
}

function el(tag, attrs = {}, children = []) {
  const node = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => {
    if (k === "class") node.className = v;
    else if (k === "text") node.textContent = v;
    else if (k.startsWith("on") && typeof v === "function") node.addEventListener(k.slice(2), v);
    else node.setAttribute(k, v);
  });
  children.forEach((c) => node.appendChild(typeof c === "string" ? document.createTextNode(c) : c));
  return node;
}

function renderError(container, message) {
  container.innerHTML = "";
  container.appendChild(
    el("div", { class: "card danger" }, [
      el("h2", {}, ["Error"]),
      el("p", {}, [message]),
      el("a", { class: "link", href: "index.php" }, ["Go back"]),
    ])
  );
}

function showApiError(prefix, e) {
  console.error(prefix, e);
  alert(
    `${prefix}\n\nStatus: ${e?.status ?? "?"}\nURL: ${e?.url ?? "?"}\nMessage: ${e?.data?.error ?? "No JSON error"}`
  );
}

async function load() {
  const app = document.getElementById("app");
  const code = (app?.dataset?.code || "").trim().toUpperCase();

  if (!/^[A-Z0-9]{8}$/.test(code)) {
    renderError(app, "Invalid code format. It must be 8 letters/numbers.");
    return;
  }

  async function refresh() {
    const data = await apiGet(`api/ticket.php?code=${encodeURIComponent(code)}`);
    const t = data.ticket;

    app.innerHTML = "";

    // Ticket info (no reservation details anymore)
    app.appendChild(
      el("div", { class: "card" }, [
        el("h2", {}, ["Ticket"]),
        el("p", { class: "muted" }, ["Ticket code: ", el("strong", {}, [t.code])]),
      ])
    );

    // Current table
    const current = t.current_table;
    app.appendChild(
      el("div", { class: "card" }, [
        el("div", { class: "row space" }, [
          el("div", {}, [
            el("h2", {}, ["Your current table"]),
            el("p", { class: "muted" }, [
              current
                ? `${current.label} (capacity ${current.capacity})`
                : "No table selected yet.",
            ]),
          ]),
          current
            ? el(
                "button",
                {
                  type: "button",
                  class: "btn danger",
                  onclick: async () => {
                    try {
                      await apiPost("api/release.php", { code });
                      await refresh();
                    } catch (e) {
                      showApiError("Release failed", e);
                    }
                  },
                },
                ["Untick table"]
              )
            : el("span", { class: "muted" }, [""]),
        ]),
      ])
    );

    // Available tables
    app.appendChild(el("h2", {}, ["Select a table"]));

    const grid = el("div", { class: "grid" });

    const tables = Array.isArray(data.available_tables) ? data.available_tables : [];
    if (!tables.length) {
      grid.appendChild(
        el("div", { class: "card" }, [el("p", { class: "muted" }, ["No tables available right now."])])
      );
    } else {
      tables.forEach((tb) => {
        grid.appendChild(
          el("div", { class: "card" }, [
            el("h3", {}, [`Table ${tb.label}`]),
            el("p", { class: "muted" }, [
              `Capacity: ${tb.capacity}` + (tb.remaining != null ? ` • Remaining: ${tb.remaining}` : ""),
            ]),
            el(
              "button",
              {
                type: "button",
                class: "btn",
                onclick: async () => {
                  try {
                    await apiPost("api/assign.php", { code, table_id: tb.id });
                    await refresh();
                  } catch (e) {
                    if (e?.status === 409) {
                      alert("That table is full or was just taken. Pick another.");
                      try { await refresh(); } catch (_) {}
                      return;
                    }
                    showApiError("Assign failed", e);
                  }
                },
              },
              ["Select table"]
            ),
          ])
        );
      });
    }

    app.appendChild(grid);
  }

  try {
    await refresh();
  } catch (e) {
    console.error("LOAD FAILED", e);
    renderError(
      app,
      `Could not load ticket. Status: ${e?.status ?? "?"} URL: ${e?.url ?? "?"} Message: ${e?.data?.error ?? "No JSON"}`
    );
  }
}

window.addEventListener("DOMContentLoaded", load);
