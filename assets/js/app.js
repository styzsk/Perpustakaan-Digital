/* ==========================================================================
   BukuHub — JavaScript utama.
   Berisi interaksi ringan untuk melengkapi halaman yang dirender PHP.
   ========================================================================== */

(() => {
  "use strict";

  const $ = (selector, parent = document) => parent.querySelector(selector);
  const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
  const text = (element, value) => {
    if (element) element.textContent = value ?? "";
  };

  const ICONS = {
    success: "check",
    error: "alert",
    danger: "alert",
    warning: "alert",
    info: "sparkles",
  };

  /* ------------------------------- Toast -------------------------------- */

  function dismissToast(toast) {
    if (!toast || toast.classList.contains("is-leaving")) return;
    toast.classList.add("is-leaving");
    setTimeout(() => toast.remove(), 250);
  }

  function bindToast(toast) {
    if (!toast || toast.dataset.bound === "1") return;
    toast.dataset.bound = "1";

    let timer = setTimeout(() => dismissToast(toast), 5200);

    toast.addEventListener("mouseenter", () => clearTimeout(timer));
    toast.addEventListener("mouseleave", () => {
      clearTimeout(timer);
      timer = setTimeout(() => dismissToast(toast), 2000);
    });

    $(".app-toast-close", toast)?.addEventListener("click", () => dismissToast(toast));
  }

  function toast(message, type = "info") {
    const stack = $("#toastStack");
    if (!stack) return;

    const kind = ICONS[type] ? type : "info";
    const element = document.createElement("div");
    element.className = `app-toast app-toast--${kind}`;
    element.dataset.toast = "";
    element.innerHTML = `
      <span class="app-toast-icon"><svg class="icon"><use href="#i-${ICONS[kind]}"/></svg></span>
      <p></p>
      <button class="app-toast-close" type="button" aria-label="Tutup notifikasi">
        <svg class="icon"><use href="#i-x"/></svg>
      </button>`;
    $("p", element).textContent = message ?? "";

    while (stack.children.length >= 4) stack.firstElementChild.remove();
    stack.appendChild(element);
    bindToast(element);
  }

  $$("[data-toast]").forEach(bindToast);

  /* ------------------------------- Modal -------------------------------- */

  const MODALS = {
    book: "bookModal",
    member: "memberModal",
    loan: "loanModal",
    confirm: "confirmModal",
  };

  function modal(name) {
    const element = document.getElementById(MODALS[name] || name);
    return element && window.bootstrap?.Modal
      ? window.bootstrap.Modal.getOrCreateInstance(element)
      : null;
  }

  const showModal = (name) => modal(name)?.show();

  function clearErrors(form) {
    if (!form) return;
    $$(".form-field.is-invalid", form).forEach((field) => field.classList.remove("is-invalid"));
    $$("[aria-invalid='true']", form).forEach((field) => field.removeAttribute("aria-invalid"));
  }

  function fillForm(form, values = {}) {
    if (!form) return;

    form.reset();
    clearErrors(form);

    const idField = form.querySelector('[name="id"]');
    if (idField) idField.value = values.id ?? 0;

    Object.entries(values).forEach(([name, value]) => {
      const field = form.elements[name];
      if (field) field.value = value ?? "";
    });
  }

  function setSubmitLabel(button, label) {
    if (button) button.innerHTML = `<svg class="icon"><use href="#i-check"/></svg> ${label}`;
  }

  function openBookModal(book = null) {
    const form = $("#bookForm");
    if (!form) {
      toast("Form buku tidak tersedia di halaman ini.", "error");
      return;
    }

    const edit = Boolean(book?.id_buku);
    fillForm(form, edit ? { ...book, id: book.id_buku } : { jumlah_eksemplar: 1 });

    text($("#bookModalTitle"), edit ? "Ubah data buku" : "Tambah buku baru");
    text($("#bookModalSub"), edit
      ? "Perbarui metadata atau jumlah eksemplar buku ini."
      : "Lengkapi metadata dan jumlah eksemplar.");
    setSubmitLabel($("#bookSubmit"), edit ? "Simpan perubahan" : "Simpan buku");

    showModal("book");
  }

  function openMemberModal(member = null) {
    const form = $("#memberForm");
    if (!form) {
      toast("Form anggota tidak tersedia di halaman ini.", "error");
      return;
    }

    const edit = Boolean(member?.id_anggota);
    fillForm(form, edit ? { ...member, id: member.id_anggota } : { status: "aktif" });

    text($("#memberModalTitle"), edit ? "Ubah data anggota" : "Tambah anggota baru");
    text($("#memberModalSub"), edit
      ? "Perbarui profil, batas pinjam, atau status keanggotaan."
      : "Daftarkan anggota dan tentukan batas pinjam.");
    setSubmitLabel($("#memberSubmit"), edit ? "Simpan perubahan" : "Simpan anggota");

    showModal("member");
  }

  /* --------------------------- Pratinjau pinjam -------------------------- */

  function formatDate(iso) {
    if (!iso) return "—";
    const [year, month, day] = iso.split("-").map(Number);
    const date = new Date(year, month - 1, day);
    if (Number.isNaN(date.getTime())) return iso;

    return new Intl.DateTimeFormat("id-ID", {
      day: "numeric",
      month: "long",
      year: "numeric",
    }).format(date);
  }

  function addDays(iso, days) {
    const [year, month, day] = String(iso || "").split("-").map(Number);
    if (!year || !month || !day) return "";

    const date = new Date(year, month - 1, day + Number(days || 0));
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, "0"),
      String(date.getDate()).padStart(2, "0"),
    ].join("-");
  }

  function updateLoanPreview() {
    const form = $("#loanForm");
    const memberSelect = $("#loanMember");
    const bookSelect = $("#loanBook");
    const preview = $("#loanPreview");
    if (!form || !memberSelect || !bookSelect || !preview) return;

    if (!memberSelect.value || !bookSelect.value) {
      preview.innerHTML = '<span class="loan-preview-hint">Pilih anggota dan buku untuk melihat pratinjau peminjaman.</span>';
      return;
    }

    const memberOption = memberSelect.selectedOptions[0];
    const bookOption = bookSelect.selectedOptions[0];
    const active = Number(memberOption.dataset.active || 0);
    const limit = Number(memberOption.dataset.limit || 0);
    const stock = Number(bookOption.dataset.stock || 0);
    const duration = Number(form.dataset.duration || 7);
    const tariff = Number(form.dataset.tariff || 1000);
    const borrowDate = $("#loanDate")?.value || "";
    const dueDate = borrowDate ? addDays(borrowDate, duration) : "";

    const rows = [
      ["Anggota", memberOption.dataset.name || memberOption.textContent.trim()],
      ["Beban pinjam", `${active} / ${limit} aktif`],
      ["Stok tersedia", `${stock} eksemplar`],
      ["Tanggal pinjam", formatDate(borrowDate)],
      ["Jatuh tempo", formatDate(dueDate)],
      ["Denda keterlambatan", `Rp${tariff.toLocaleString("id-ID")} / hari`],
    ];

    preview.innerHTML = rows
      .map(([label, value]) => `<div class="loan-preview-row"><span>${label}</span><strong>${value}</strong></div>`)
      .join("");

    if (limit - active <= 0) {
      const warning = document.createElement("p");
      warning.className = "loan-preview-warning";
      warning.textContent = "Anggota ini sudah mencapai batas peminjaman.";
      preview.appendChild(warning);
    }
  }

  function openLoanModal() {
    const form = $("#loanForm");
    if (form) {
      fillForm(form);
      const date = $("#loanDate");
      if (date && !date.value) date.value = date.max || "";
      updateLoanPreview();
    }
    showModal("loan");
  }

  ["loanMember", "loanBook", "loanDate"].forEach((id) => {
    const field = document.getElementById(id);
    if (!field) return;
    field.addEventListener("change", updateLoanPreview);
    field.addEventListener("input", updateLoanPreview);
  });

  /* ----------------------------- Konfirmasi ----------------------------- */

  function openConfirm(button) {
    const form = $("#confirmForm");
    if (!form) return;

    form.action = button.dataset.url || "#";
    form.removeAttribute("data-submitting");
    $("#confirmId").value = button.dataset.id || "";

    text($("#confirmTitle"), button.dataset.title || "Konfirmasi tindakan");
    text($("#confirmSub"), button.dataset.sub || "Tindakan ini akan langsung diproses.");
    text($("#confirmOk"), button.dataset.ok || "Ya, lanjutkan");
    $("#confirmMessage").innerHTML = button.dataset.message || "Apakah Anda yakin?";

    const danger = button.dataset.danger === "1";
    const ok = $("#confirmOk");
    ok.className = danger ? "btn-danger" : "btn-add btn-add--modal";
    ok.disabled = false;
    $("#confirmIcon")?.classList.toggle("modal-icon--danger", danger);

    showModal("confirm");
  }

  /* ------------------------------ Aksi klik ----------------------------- */

  document.addEventListener("click", (event) => {
    const trigger = event.target.closest(
      "[data-modal-open], [data-book-edit], [data-member-edit], [data-confirm]"
    );
    if (!trigger) return;

    if (trigger.dataset.modalOpen) {
      event.preventDefault();
      const type = trigger.dataset.modalOpen;
      if (type === "book") openBookModal();
      else if (type === "member") openMemberModal();
      else if (type === "loan") openLoanModal();
      else showModal(type);
      return;
    }

    if (trigger.hasAttribute("data-book-edit")) {
      event.preventDefault();
      openBookModal(JSON.parse(trigger.dataset.book || "null"));
      return;
    }

    if (trigger.hasAttribute("data-member-edit")) {
      event.preventDefault();
      openMemberModal(JSON.parse(trigger.dataset.member || "null"));
      return;
    }

    event.preventDefault();
    openConfirm(trigger);
  });

  /* -------------------------------- Form -------------------------------- */

  function validateForm(form) {
    clearErrors(form);

    const invalid = $$("input, select, textarea", form).find((field) => {
      if (field.disabled || field.type === "hidden" || !field.willValidate) return false;
      return !field.checkValidity();
    });

    if (!invalid) return true;

    invalid.closest(".form-field")?.classList.add("is-invalid");
    invalid.setAttribute("aria-invalid", "true");

    const label = form.querySelector(`label[for="${invalid.id}"]`)?.textContent.replace("*", "").trim();
    toast(`Periksa kembali kolom ${label || "yang wajib diisi"}.`, "error");
    invalid.focus();
    return false;
  }

  $$("form").forEach((form) => {
    if ((form.getAttribute("method") || "get").toLowerCase() !== "post") return;

    $$("input, select, textarea", form).forEach((field) => {
      field.addEventListener("input", () => clearErrors(form));
      field.addEventListener("change", () => clearErrors(form));
    });

    form.addEventListener("submit", (event) => {
      if (form.dataset.submitting === "1") {
        event.preventDefault();
        return;
      }

      if (!validateForm(form)) {
        event.preventDefault();
        return;
      }

      form.dataset.submitting = "1";
      $$('button[type="submit"]', form).forEach((button) => (button.disabled = true));
    });
  });

  window.addEventListener("pageshow", () => {
    $$("form[data-submitting='1']").forEach((form) => {
      delete form.dataset.submitting;
      $$('button[type="submit"]', form).forEach((button) => (button.disabled = false));
    });
  });

  /* ------------------------------- Filter ------------------------------- */

  $$("[data-auto-submit]").forEach((field) => {
    field.addEventListener("change", () => {
      if (field.id === "reportPreset" && field.value === "custom") {
        $("#reportFrom")?.focus();
        return;
      }
      field.closest("form")?.requestSubmit();
    });
  });

  $$("[data-report-date]").forEach((field) => {
    field.addEventListener("change", () => {
      const preset = $("#reportPreset");
      if (preset) preset.value = "custom";
      field.closest("form")?.requestSubmit();
    });
  });

  /* ------------------------------- Grafik ------------------------------- */

  $$(".chart-plot").forEach((plot) => {
    const tooltip = $(".chart-tooltip", plot);
    if (!tooltip) return;

    const month = $(".tt-month", tooltip);
    const values = $$(".tt-row strong", tooltip);

    const show = (group) => {
      text(month, group.dataset.month || "—");
      text(values[0], group.dataset.current || "0");
      text(values[1], group.dataset.previous || "0");

      const center = group.offsetLeft + group.offsetWidth / 2;
      tooltip.style.left = `${Math.min(plot.clientWidth - 88, Math.max(88, center))}px`;
      tooltip.style.top = `${Math.max(46, $(".bar--current", group)?.offsetTop || 10)}px`;
      tooltip.classList.add("is-visible");
      tooltip.setAttribute("aria-hidden", "false");
    };

    const hide = () => {
      tooltip.classList.remove("is-visible");
      tooltip.setAttribute("aria-hidden", "true");
    };

    $$(".bar-group", plot).forEach((group) => {
      group.addEventListener("mouseenter", () => show(group));
      group.addEventListener("mouseleave", hide);
      group.addEventListener("focus", () => show(group));
      group.addEventListener("blur", hide);
    });
  });

  $$(".donut").forEach((donut) => {
    $$(".donut-seg", donut).forEach((segment) => {
      const active = (state) => {
        donut.classList.toggle("is-dim", state);
        segment.classList.toggle("is-active", state);
      };

      segment.addEventListener("mouseenter", () => active(true));
      segment.addEventListener("mouseleave", () => active(false));
      segment.addEventListener("focus", () => active(true));
      segment.addEventListener("blur", () => active(false));
    });
  });

  /* ------------------------------- Sidebar ------------------------------ */

  const sidebar = $("#sidebar");
  const backdrop = $("#sidebarBackdrop");
  const sidebarOpen = $("#sidebarOpen");

  function toggleSidebar(open) {
    sidebar?.classList.toggle("is-open", open);
    backdrop?.classList.toggle("is-open", open);
    sidebarOpen?.setAttribute("aria-expanded", String(open));
    document.body.classList.toggle("has-sidebar-open", open);
  }

  sidebarOpen?.addEventListener("click", () => toggleSidebar(true));
  $("#sidebarClose")?.addEventListener("click", () => toggleSidebar(false));
  backdrop?.addEventListener("click", () => toggleSidebar(false));
  $$(".side-link").forEach((link) => link.addEventListener("click", () => {
    if (window.innerWidth <= 1120) toggleSidebar(false);
  }));
  window.addEventListener("resize", () => {
    if (window.innerWidth > 1120) toggleSidebar(false);
  });

  /* ----------------------------- Notifikasi ----------------------------- */

  function refreshNotifications() {
    const items = $$(".notif-item");
    const unread = items.filter((item) => item.classList.contains("is-unread")).length;

    $("#notifDot")?.classList.toggle("is-hidden", unread === 0);
    text($("#notifSummary"), unread > 0 ? `${unread} hal perlu perhatian` : "Semua aman");
    $(".bell-btn")?.setAttribute("aria-label", `Notifikasi, ${unread} item`);
  }

  $$(".notif-item").forEach((item) => {
    item.addEventListener("click", () => {
      item.classList.remove("is-unread");
      item.classList.add("is-read");
      refreshNotifications();
    });
  });

  $("#markAllRead")?.addEventListener("click", () => {
    $$(".notif-item").forEach((item) => {
      item.classList.remove("is-unread");
      item.classList.add("is-read");
    });
    refreshNotifications();
    toast("Semua notifikasi ditandai sudah dibaca.", "success");
  });

  /* ------------------------------- Pencarian ---------------------------- */

  const search = $("#globalSearch");
  const searchWrap = $("#searchWrap");

  function syncSearch() {
    if (search && searchWrap) searchWrap.classList.toggle("has-value", search.value.length > 0);
  }

  search?.addEventListener("input", syncSearch);
  syncSearch();

  $("#searchClear")?.addEventListener("click", () => {
    if (!search) return;

    const hadValue = search.value.length > 0;
    search.value = "";
    syncSearch();
    search.focus();

    if (hadValue && document.body.dataset.page === "books") {
      search.closest("form")?.requestSubmit();
    }
  });

  /* --------------------------- Tampilkan password ----------------------- */

  $$("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = $(button.dataset.passwordToggle);
      if (!input) return;

      const reveal = input.type === "password";
      input.type = reveal ? "text" : "password";
      button.classList.toggle("is-active", reveal);
      button.setAttribute("aria-label", reveal ? "Sembunyikan password" : "Tampilkan password");
      input.focus();
    });
  });

  /* ------------------------------ Keyboard ------------------------------ */

  document.addEventListener("keydown", (event) => {
    const typing = ["INPUT", "TEXTAREA", "SELECT"].includes(event.target.tagName)
      || event.target.isContentEditable;
    const modalOpen = Boolean($(".modal.show"));

    if (!typing && !modalOpen && (event.key === "/" || (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k")) {
      if (!search) return;
      event.preventDefault();
      search.focus();
      search.select();
      return;
    }

    if (event.key !== "Escape") return;

    if (search && document.activeElement === search) {
      if (search.value) {
        search.value = "";
        syncSearch();
      } else {
        search.blur();
      }
      return;
    }

    toggleSidebar(false);
  });

  /* -------------------------------- Init -------------------------------- */

  const initialModal = document.body.dataset.openModal || "";
  if (initialModal && MODALS[initialModal]) {
    setTimeout(() => {
      if (initialModal === "loan") updateLoanPreview();
      showModal(initialModal);
    }, 80);
  }

  window.BukuHub = {
    toast,
    openModal: showModal,
    closeModal: (name) => modal(name)?.hide(),
    refreshNotifications,
  };
})();
