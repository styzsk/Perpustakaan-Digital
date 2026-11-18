/* ==========================================================================
   BukuHub — shared utilities
   Dates, currency, escaping, category palettes and table helpers.
   ========================================================================== */

window.LibrioUtils = (function () {
  "use strict";

  var MONTHS_SHORT = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  var MONTHS_LONG = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
  ];

  var CATEGORY_STYLES = {
    "Fiction": { bg: "#fff1e7", ink: "#b54708", cover: ["#e76f51", "#9a3412"] },
    "Non-fiction": { bg: "#eaf2ff", ink: "#1d4ed8", cover: ["#3b82f6", "#1e3a8a"] },
    "Science": { bg: "#e9f8f0", ink: "#0b7a4b", cover: ["#0f766e", "#134e4a"] },
    "History": { bg: "#fdf3dc", ink: "#a16207", cover: ["#d97706", "#78350f"] },
    "Biography": { bg: "#f3ecff", ink: "#6d28d9", cover: ["#7c3aed", "#4c1d95"] }
  };

  var FALLBACK_STYLE = { bg: "#f1f2f5", ink: "#4b5563", cover: ["#64748b", "#1e293b"] };

  var AVATARS = [
    ["#ffe4d6", "#b54708"], ["#e0f2fe", "#0369a1"], ["#dcfce7", "#166534"],
    ["#fae8ff", "#86198f"], ["#fef3c7", "#92400e"], ["#cffafe", "#0e7490"],
    ["#ede9fe", "#5b21b6"], ["#ffedd5", "#9a3412"], ["#f1f5f9", "#334155"]
  ];

  function pad(value) {
    return String(value).padStart(2, "0");
  }

  function parseDate(iso) {
    if (!iso) return null;
    var parts = String(iso).split("-").map(Number);
    return new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0, 0);
  }

  function toISO(date) {
    return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
  }

  function addDaysDate(date, days) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days, 12, 0, 0, 0);
  }

  function addDays(iso, days) {
    return toISO(addDaysDate(parseDate(iso), days));
  }

  function todayISO() {
    return toISO(new Date());
  }

  function dayNumber(iso) {
    var parts = String(iso).split("-").map(Number);
    return Date.UTC(parts[0], parts[1] - 1, parts[2]) / 86400000;
  }

  function diffDays(fromISO, toISOValue) {
    return Math.round(dayNumber(toISOValue) - dayNumber(fromISO));
  }

  function formatDate(iso, withYear) {
    if (!iso) return "—";
    var date = parseDate(iso);
    return date.getDate() + " " + MONTHS_SHORT[date.getMonth()] + (withYear ? " " + date.getFullYear() : "");
  }

  function formatFullDate(iso, includeYear) {
    if (!iso) return "—";
    var date = parseDate(iso);
    return MONTHS_LONG[date.getMonth()] + " " + date.getDate() + (includeYear ? ", " + date.getFullYear() : "");
  }

  function monthKey(iso) {
    return String(iso).slice(0, 7);
  }

  function formatRupiah(amount) {
    return "Rp " + new Intl.NumberFormat("id-ID").format(Math.round(Number(amount) || 0));
  }

  function fmt(value) {
    return Number(value || 0).toLocaleString("en-US");
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }

  function initials(name, count) {
    var parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return "?";
    return parts.slice(0, count || 2).map(function (part) {
      return part.charAt(0).toUpperCase();
    }).join("");
  }

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function categoryStyle(category) {
    return CATEGORY_STYLES[category] || FALLBACK_STYLE;
  }

  function avatarStyle(id) {
    var pair = AVATARS[Math.abs(Number(id) || 0) % AVATARS.length];
    return { bg: pair[0], ink: pair[1] };
  }

  function coverStyle(book) {
    return categoryStyle(book.kategori).cover;
  }

  function categoryChip(category) {
    var style = categoryStyle(category);
    return '<span class="cat-chip" style="--chip-bg:' + style.bg + ";--chip-ink:" + style.ink + '">' +
      escapeHtml(category) + "</span>";
  }

  function paginate(items, page, perPage) {
    var totalPages = Math.max(1, Math.ceil(items.length / perPage));
    var current = clamp(page, 1, totalPages);
    var start = (current - 1) * perPage;
    return {
      items: items.slice(start, start + perPage),
      page: current,
      totalPages: totalPages,
      from: items.length ? start + 1 : 0,
      to: Math.min(start + perPage, items.length),
      total: items.length
    };
  }

  function pageList(current, totalPages) {
    var pages = [];
    if (totalPages <= 7) {
      for (var i = 1; i <= totalPages; i += 1) pages.push(i);
      return pages;
    }
    pages.push(1);
    if (current > 3) pages.push("…");
    for (var page = Math.max(2, current - 1); page <= Math.min(totalPages - 1, current + 1); page += 1) {
      pages.push(page);
    }
    if (current < totalPages - 2) pages.push("…");
    pages.push(totalPages);
    return pages;
  }

  function emptyRow(colspan, message, hint) {
    return '<tr><td colspan="' + colspan + '">' +
      '<div class="empty-state">' +
        '<span class="empty-state-icon"><svg class="icon"><use href="#i-search"/></svg></span>' +
        "<h3>" + escapeHtml(message) + "</h3>" +
        "<p>" + escapeHtml(hint || "Try a different keyword or filter.") + "</p>" +
      "</div></td></tr>";
  }

  return {
    MONTHS_SHORT: MONTHS_SHORT,
    MONTHS_LONG: MONTHS_LONG,
    parseDate: parseDate,
    toISO: toISO,
    addDaysDate: addDaysDate,
    addDays: addDays,
    todayISO: todayISO,
    diffDays: diffDays,
    formatDate: formatDate,
    formatFullDate: formatFullDate,
    monthKey: monthKey,
    formatRupiah: formatRupiah,
    fmt: fmt,
    escapeHtml: escapeHtml,
    initials: initials,
    clamp: clamp,
    categoryStyle: categoryStyle,
    avatarStyle: avatarStyle,
    coverStyle: coverStyle,
    categoryChip: categoryChip,
    paginate: paginate,
    pageList: pageList,
    emptyRow: emptyRow
  };
})();
