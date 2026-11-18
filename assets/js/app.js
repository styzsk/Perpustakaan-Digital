/* ==========================================================================
   BukuHub — application controller
   Progressive enhancement untuk aplikasi PHP: sidebar, modal, pratinjau
   peminjaman, konfirmasi, validasi klien, toast, grafik, dan filter.
   Semua data tetap diproses di server.
   ========================================================================== */

(function () {
  "use strict";

  var body = document.body;
  var hasBootstrap = Boolean(window.bootstrap && window.bootstrap.Modal);
  var MODAL_IDS = {
    book: "bookModal",
    member: "memberModal",
    loan: "loanModal",
    confirm: "confirmModal"
  };
  var TOAST_ICONS = {
    success: "check",
    error: "alert",
    danger: "alert",
    warning: "alert",
    info: "sparkles"
  };

  function $(selector, context) {
    return (context || document).querySelector(selector);
  }

  function $$(selector, context) {
    return Array.prototype.slice.call((context || document).querySelectorAll(selector));
  }

  function setText(element, value) {
    if (element) element.textContent = value == null ? "" : String(value);
  }

  function parseJsonAttribute(element, attribute) {
    if (!element) return null;
    try {
      return JSON.parse(element.getAttribute(attribute) || "null");
    } catch (error) {
      return null;
    }
  }

  /* ------------------------------- Toasts ------------------------------- */

  function dismissToast(element) {
    if (!element || element.classList.contains("is-leaving")) return;
    element.classList.add("is-leaving");
    window.setTimeout(function () {
      if (element.parentNode) element.parentNode.removeChild(element);
    }, 250);
  }

  function bindToast(element) {
    if (!element || element.getAttribute("data-toast-bound") === "1") return;
    element.setAttribute("data-toast-bound", "1");

    var timer = window.setTimeout(function () {
      dismissToast(element);
    }, 5200);

    element.addEventListener("mouseenter", function () {
      window.clearTimeout(timer);
    });

    element.addEventListener("mouseleave", function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        dismissToast(element);
      }, 2000);
    });

    var close = $(".app-toast-close", element);
    if (close) {
      close.addEventListener("click", function () {
        dismissToast(element);
      });
    }
  }

  function toast(message, type) {
    var stack = document.getElementById("toastStack");
    if (!stack) return;

    var kind = TOAST_ICONS[type] ? type : "info";
    var element = document.createElement("div");
    element.className = "app-toast app-toast--" + kind;
    element.setAttribute("data-toast", "");

    var icon = document.createElement("span");
    icon.className = "app-toast-icon";
    icon.innerHTML = '<svg class="icon"><use href="#i-' + TOAST_ICONS[kind] + '"/></svg>';

    var copy = document.createElement("p");
    copy.textContent = message == null ? "" : String(message);

    var close = document.createElement("button");
    close.type = "button";
    close.className = "app-toast-close";
    close.setAttribute("aria-label", "Tutup notifikasi");
    close.innerHTML = '<svg class="icon"><use href="#i-x"/></svg>';

    element.appendChild(icon);
    element.appendChild(copy);
    element.appendChild(close);

    while (stack.children.length >= 4) {
      stack.removeChild(stack.firstElementChild);
    }

    stack.appendChild(element);
    bindToast(element);
  }

  $$("[data-toast]").forEach(bindToast);

  /* ------------------------------- Modals ------------------------------- */

  function getModal(name) {
    if (!hasBootstrap) return null;
    var id = MODAL_IDS[name] || name;
    var element = document.getElementById(id);
    if (!element) return null;
    return window.bootstrap.Modal.getOrCreateInstance(element);
  }

  function showModal(name) {
    var modal = getModal(name);
    if (modal) modal.show();
  }

  function hideModal(name) {
    var modal = getModal(name);
    if (modal) modal.hide();
  }

  function clearFieldErrors(form) {
    if (!form) return;
    $$(".form-field.is-invalid", form).forEach(function (field) {
      field.classList.remove("is-invalid");
    });
    $$("[aria-invalid='true']", form).forEach(function (input) {
      input.removeAttribute("aria-invalid");
    });
  }

  function markInvalid(field, invalid) {
    if (!field) return;
    var wrapper = field.closest(".form-field");
    if (wrapper) wrapper.classList.toggle("is-invalid", Boolean(invalid));
    if (invalid) field.setAttribute("aria-invalid", "true");
    else field.removeAttribute("aria-invalid");
  }

  function clearForm(form) {
    if (!form) return;
    form.reset();
    clearFieldErrors(form);
    var idField = form.querySelector('input[name="id"]');
    if (idField) idField.value = "0";
  }

  function bindFieldClearing(form) {
    if (!form || form.getAttribute("data-clear-bound") === "1") return;
    form.setAttribute("data-clear-bound", "1");
    $$("input, select, textarea", form).forEach(function (field) {
      field.addEventListener("input", function () {
        markInvalid(field, false);
      });
      field.addEventListener("change", function () {
        markInvalid(field, false);
      });
    });
  }

  function fieldLabel(field) {
    if (!field) return "Kolom";
    var label = field.id ? document.querySelector('label[for="' + field.id + '"]') : null;
    if (!label && field.closest(".form-field")) {
      label = field.closest(".form-field").querySelector("label");
    }
    return label ? label.textContent.replace(/\*/g, "").trim() : "Kolom";
  }

  function validateForm(form) {
    if (!form) return true;
    clearFieldErrors(form);

    var fields = $$("input, select, textarea", form);
    var invalid = [];

    fields.forEach(function (field) {
      if (field.disabled || field.type === "hidden") return;
      if (!field.willValidate) return;
      if (field.checkValidity()) return;

      var validity = field.validity || {};
      var relevant = validity.valueMissing || validity.typeMismatch || validity.patternMismatch ||
        validity.rangeUnderflow || validity.rangeOverflow || validity.stepMismatch ||
        validity.tooShort || validity.tooLong || validity.badInput;

      if (relevant) {
        markInvalid(field, true);
        invalid.push(field);
      }
    });

    if (invalid.length > 0) {
      toast("Periksa kembali kolom " + fieldLabel(invalid[0]) + " sebelum menyimpan.", "error");
      invalid[0].focus();
      return false;
    }

    return true;
  }

  /* ----------------------------- Book modal ----------------------------- */

  function openBookModal(book) {
    var form = document.getElementById("bookForm");
    if (!form) {
      toast("Form buku tidak tersedia di halaman ini.", "error");
      return;
    }

    clearForm(form);
    bindFieldClearing(form);

    var isEdit = Boolean(book && book.id_buku);

    if (isEdit) {
      setText(document.getElementById("bookModalTitle"), "Ubah data buku");
      setText(document.getElementById("bookModalSub"), "Perbarui metadata atau jumlah eksemplar buku ini.");
      document.getElementById("bookId").value = book.id_buku;
      document.getElementById("bookTitle").value = book.judul || "";
      document.getElementById("bookAuthor").value = book.pengarang || "";
      document.getElementById("bookCategory").value = book.kategori || "";
      document.getElementById("bookIsbn").value = book.isbn || "";
      document.getElementById("bookPublisher").value = book.penerbit || "";
      document.getElementById("bookYear").value = book.tahun_terbit || "";
      document.getElementById("bookCopies").value = book.jumlah_eksemplar || 1;
      document.getElementById("bookSynopsis").value = book.sinopsis || "";
      var submit = document.getElementById("bookSubmit");
      if (submit) submit.innerHTML = '<svg class="icon"><use href="#i-check"/></svg> Simpan perubahan';
    } else {
      setText(document.getElementById("bookModalTitle"), "Tambah buku baru");
      setText(document.getElementById("bookModalSub"), "Lengkapi metadata dan jumlah eksemplar.");
      var submitNew = document.getElementById("bookSubmit");
      if (submitNew) submitNew.innerHTML = '<svg class="icon"><use href="#i-check"/></svg> Simpan buku';
    }

    showModal("book");
  }

  /* ---------------------------- Member modal ---------------------------- */

  function openMemberModal(member) {
    var form = document.getElementById("memberForm");
    if (!form) {
      toast("Form anggota tidak tersedia di halaman ini.", "error");
      return;
    }

    clearForm(form);
    bindFieldClearing(form);

    var isEdit = Boolean(member && member.id_anggota);

    if (isEdit) {
      setText(document.getElementById("memberModalTitle"), "Ubah data anggota");
      setText(document.getElementById("memberModalSub"), "Perbarui profil, batas pinjam, atau status keanggotaan.");
      document.getElementById("memberId").value = member.id_anggota;
      document.getElementById("memberName").value = member.nama || "";
      document.getElementById("memberNim").value = member.nim_nik || "";
      document.getElementById("memberEmail").value = member.email || "";
      document.getElementById("memberPhone").value = member.telepon || "";
      document.getElementById("memberLimit").value = member.maks_pinjam || 3;
      document.getElementById("memberStatus").value = member.status || "aktif";
      document.getElementById("memberAddress").value = member.alamat || "";
      var submit = document.getElementById("memberSubmit");
      if (submit) submit.innerHTML = '<svg class="icon"><use href="#i-check"/></svg> Simpan perubahan';
    } else {
      setText(document.getElementById("memberModalTitle"), "Tambah anggota baru");
      setText(document.getElementById("memberModalSub"), "Daftarkan anggota dan tentukan batas pinjam.");
      var submitNew = document.getElementById("memberSubmit");
      if (submitNew) submitNew.innerHTML = '<svg class="icon"><use href="#i-check"/></svg> Simpan anggota';
    }

    showModal("member");
  }

  /* ----------------------------- Loan modal ----------------------------- */

  function formatDateId(isoDate) {
    if (!isoDate) return "—";
    var parts = String(isoDate).split("-");
    if (parts.length !== 3) return isoDate;
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (isNaN(date.getTime())) return isoDate;
    try {
      return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric"
      }).format(date);
    } catch (error) {
      return isoDate;
    }
  }

  function addDays(isoDate, days) {
    var parts = String(isoDate || "").split("-");
    if (parts.length !== 3) return "";
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (isNaN(date.getTime())) return "";
    date.setDate(date.getDate() + Number(days || 0));
    var month = ("0" + (date.getMonth() + 1)).slice(-2);
    var day = ("0" + date.getDate()).slice(-2);
    return date.getFullYear() + "-" + month + "-" + day;
  }

  function updateLoanPreview() {
    var form = document.getElementById("loanForm");
    var memberSelect = document.getElementById("loanMember");
    var bookSelect = document.getElementById("loanBook");
    var dateInput = document.getElementById("loanDate");
    var preview = document.getElementById("loanPreview");

    if (!form || !memberSelect || !bookSelect || !preview) return;

    if (!memberSelect.value || !bookSelect.value) {
      preview.innerHTML = '<span class="loan-preview-hint">Pilih anggota dan buku untuk melihat pratinjau peminjaman.</span>';
      return;
    }

    var memberOption = memberSelect.options[memberSelect.selectedIndex];
    var bookOption = bookSelect.options[bookSelect.selectedIndex];
    var active = parseInt(memberOption.getAttribute("data-active") || "0", 10);
    var limit = parseInt(memberOption.getAttribute("data-limit") || "0", 10);
    var stock = parseInt(bookOption.getAttribute("data-stock") || "0", 10);
    var duration = parseInt(form.getAttribute("data-duration") || "7", 10);
    var tariff = parseInt(form.getAttribute("data-tariff") || "1000", 10);
    var borrowDate = dateInput && dateInput.value ? dateInput.value : "";
    var dueDate = borrowDate ? addDays(borrowDate, duration) : "";
    var remaining = limit - active;

    preview.textContent = "";

    function addRow(label, value, tone) {
      var row = document.createElement("div");
      row.className = "loan-preview-row";
      var labelElement = document.createElement("span");
      labelElement.textContent = label;
      var valueElement = document.createElement("strong");
      valueElement.textContent = value;
      if (tone) valueElement.className = tone;
      row.appendChild(labelElement);
      row.appendChild(valueElement);
      preview.appendChild(row);
    }

    addRow("Anggota", memberOption.getAttribute("data-name") || memberOption.textContent.trim());
    addRow("Beban pinjam", active + " / " + limit + " aktif");
    addRow("Stok tersedia", stock + " eksemplar");
    addRow("Tanggal pinjam", formatDateId(borrowDate));
    addRow("Jatuh tempo", formatDateId(dueDate));
    addRow("Denda keterlambatan", "Rp" + tariff.toLocaleString("id-ID") + " / hari");

    if (remaining <= 0) {
      var warning = document.createElement("p");
      warning.className = "loan-preview-warning";
      warning.textContent = "Anggota ini sudah mencapai batas peminjaman.";
      preview.appendChild(warning);
    }
  }

  (function bindLoanPreview() {
    var form = document.getElementById("loanForm");
    if (!form) return;
    ["loanMember", "loanBook", "loanDate"].forEach(function (id) {
      var field = document.getElementById(id);
      if (!field) return;
      field.addEventListener("change", updateLoanPreview);
      field.addEventListener("input", updateLoanPreview);
    });
    updateLoanPreview();
  })();

  /* --------------------------- Confirm dialog --------------------------- */

  function openConfirm(button) {
    var form = document.getElementById("confirmForm");
    if (!form) return;

    var url = button.getAttribute("data-url") || "#";
    var id = button.getAttribute("data-id") || "";
    var danger = button.getAttribute("data-danger") === "1";

    form.setAttribute("action", url);
    form.removeAttribute("data-submitting");

    var idField = document.getElementById("confirmId");
    if (idField) idField.value = id;

    setText(document.getElementById("confirmTitle"), button.getAttribute("data-title") || "Konfirmasi tindakan");
    setText(document.getElementById("confirmSub"), button.getAttribute("data-sub") || "Tindakan ini akan langsung diproses.");
    setText(document.getElementById("confirmOk"), button.getAttribute("data-ok") || "Ya, lanjutkan");

    var message = document.getElementById("confirmMessage");
    if (message) {
      message.innerHTML = button.getAttribute("data-message") || "Apakah Anda yakin?";
    }

    var ok = document.getElementById("confirmOk");
    if (ok) {
      ok.className = danger ? "btn-danger" : "btn-add btn-add--modal";
      ok.disabled = false;
    }

    var icon = document.getElementById("confirmIcon");
    if (icon) icon.classList.toggle("modal-icon--danger", danger);

    showModal("confirm");
  }

  /* ------------------------------ Actions ------------------------------- */

  function openFromTrigger(type) {
    if (type === "book") openBookModal(null);
    else if (type === "member") openMemberModal(null);
    else if (type === "loan") {
      var form = document.getElementById("loanForm");
      if (form) {
        clearForm(form);
        var date = document.getElementById("loanDate");
        if (date && !date.value) date.value = date.getAttribute("max") || "";
        updateLoanPreview();
      }
      showModal("loan");
    }
  }

  document.addEventListener("click", function (event) {
    var target = event.target;
    if (!target || !target.closest) return;

    var modalTrigger = target.closest("[data-modal-open]");
    if (modalTrigger) {
      event.preventDefault();
      openFromTrigger(modalTrigger.getAttribute("data-modal-open"));
      return;
    }

    var bookEdit = target.closest("[data-book-edit]");
    if (bookEdit) {
      event.preventDefault();
      openBookModal(parseJsonAttribute(bookEdit, "data-book"));
      return;
    }

    var memberEdit = target.closest("[data-member-edit]");
    if (memberEdit) {
      event.preventDefault();
      openMemberModal(parseJsonAttribute(memberEdit, "data-member"));
      return;
    }

    var confirmTrigger = target.closest("[data-confirm]");
    if (confirmTrigger) {
      event.preventDefault();
      openConfirm(confirmTrigger);
    }
  });

  /* ------------------------------- Forms -------------------------------- */

  $$("form").forEach(function (form) {
    if ((form.getAttribute("method") || "get").toLowerCase() !== "post") return;
    bindFieldClearing(form);

    form.addEventListener("submit", function (event) {
      if (form.getAttribute("data-submitting") === "1") {
        event.preventDefault();
        return;
      }

      if (!validateForm(form)) {
        event.preventDefault();
        return;
      }

      form.setAttribute("data-submitting", "1");
      $$('button[type="submit"]', form).forEach(function (button) {
        button.disabled = true;
      });
    });
  });

  window.addEventListener("pageshow", function () {
    $$("form[data-submitting='1']").forEach(function (form) {
      form.removeAttribute("data-submitting");
      $$('button[type="submit"]', form).forEach(function (button) {
        button.disabled = false;
      });
    });
  });

  /* ---------------------------- Auto filters ---------------------------- */

  $$("[data-auto-submit]").forEach(function (control) {
    control.addEventListener("change", function () {
      var form = control.closest("form");
      if (!form) return;

      if (control.id === "reportPreset" && control.value === "custom") {
        var from = document.getElementById("reportFrom");
        if (from) from.focus();
        return;
      }

      if (typeof form.requestSubmit === "function") form.requestSubmit();
      else form.submit();
    });
  });

  $$("[data-report-date]").forEach(function (input) {
    input.addEventListener("change", function () {
      var preset = document.getElementById("reportPreset");
      if (preset && preset.value !== "custom") preset.value = "custom";
      var form = input.closest("form");
      if (!form) return;
      if (typeof form.requestSubmit === "function") form.requestSubmit();
      else form.submit();
    });
  });

  /* ------------------------------- Charts ------------------------------- */

  $$(".chart-plot").forEach(function (plot) {
    var tooltip = $(".chart-tooltip", plot);
    if (!tooltip) return;

    var monthLabel = $(".tt-month", tooltip);
    var values = $$(".tt-row strong", tooltip);
    var groups = $$(".bar-group", plot);

    function showTooltip(group) {
      var currentBar = $(".bar--current", group);
      var left = group.offsetLeft + group.offsetWidth / 2;
      var top = currentBar ? currentBar.offsetTop : 10;

      setText(monthLabel, group.getAttribute("data-month") || "—");
      if (values[0]) setText(values[0], group.getAttribute("data-current") || "0");
      if (values[1]) setText(values[1], group.getAttribute("data-previous") || "0");

      var padding = 88;
      var maxLeft = Math.max(padding, plot.clientWidth - padding);
      tooltip.style.left = Math.min(maxLeft, Math.max(padding, left)) + "px";
      tooltip.style.top = Math.max(46, top) + "px";
      tooltip.classList.add("is-visible");
      tooltip.setAttribute("aria-hidden", "false");
    }

    function hideTooltip() {
      tooltip.classList.remove("is-visible");
      tooltip.setAttribute("aria-hidden", "true");
    }

    groups.forEach(function (group) {
      group.addEventListener("mouseenter", function () {
        showTooltip(group);
      });
      group.addEventListener("mouseleave", hideTooltip);
      group.addEventListener("focus", function () {
        showTooltip(group);
      });
      group.addEventListener("blur", hideTooltip);
    });
  });

  $$(".donut").forEach(function (donut) {
    var segments = $$(".donut-seg", donut);

    function activate(segment) {
      donut.classList.add("is-dim");
      segment.classList.add("is-active");
    }

    function deactivate(segment) {
      donut.classList.remove("is-dim");
      segment.classList.remove("is-active");
    }

    segments.forEach(function (segment) {
      segment.addEventListener("mouseenter", function () {
        activate(segment);
      });
      segment.addEventListener("mouseleave", function () {
        deactivate(segment);
      });
      segment.addEventListener("focus", function () {
        activate(segment);
      });
      segment.addEventListener("blur", function () {
        deactivate(segment);
      });
    });
  });

  /* ------------------------------ Sidebar ------------------------------- */

  var sidebar = document.getElementById("sidebar");
  var backdrop = document.getElementById("sidebarBackdrop");
  var sidebarOpen = document.getElementById("sidebarOpen");
  var sidebarClose = document.getElementById("sidebarClose");

  function openSidebar() {
    if (!sidebar || !backdrop) return;
    sidebar.classList.add("is-open");
    backdrop.classList.add("is-open");
    if (sidebarOpen) sidebarOpen.setAttribute("aria-expanded", "true");
    body.classList.add("has-sidebar-open");
  }

  function closeSidebar() {
    if (!sidebar || !backdrop) return;
    sidebar.classList.remove("is-open");
    backdrop.classList.remove("is-open");
    if (sidebarOpen) sidebarOpen.setAttribute("aria-expanded", "false");
    body.classList.remove("has-sidebar-open");
  }

  if (sidebarOpen) sidebarOpen.addEventListener("click", openSidebar);
  if (sidebarClose) sidebarClose.addEventListener("click", closeSidebar);
  if (backdrop) backdrop.addEventListener("click", closeSidebar);

  $$(".side-link").forEach(function (link) {
    link.addEventListener("click", function () {
      if (window.innerWidth <= 1120) closeSidebar();
    });
  });

  window.addEventListener("resize", function () {
    if (window.innerWidth > 1120) closeSidebar();
  });

  /* --------------------------- Notifications ---------------------------- */

  function refreshNotifications() {
    var items = $$(".notif-item");
    var unread = items.filter(function (item) {
      return item.classList.contains("is-unread");
    }).length;
    var dot = document.getElementById("notifDot");
    var summary = document.getElementById("notifSummary");
    var bell = $(".bell-btn");

    if (dot) dot.classList.toggle("is-hidden", unread === 0);
    setText(summary, unread > 0 ? unread + " hal perlu perhatian" : "Semua aman");
    if (bell) bell.setAttribute("aria-label", "Notifikasi, " + unread + " item");
  }

  $$(".notif-item").forEach(function (item) {
    item.addEventListener("click", function () {
      item.classList.remove("is-unread");
      item.classList.add("is-read");
      refreshNotifications();
    });
  });

  var markAllRead = document.getElementById("markAllRead");
  if (markAllRead) {
    markAllRead.addEventListener("click", function () {
      $$(".notif-item").forEach(function (item) {
        item.classList.remove("is-unread");
        item.classList.add("is-read");
      });
      refreshNotifications();
      toast("Semua notifikasi ditandai sudah dibaca.", "success");
    });
  }

  /* ------------------------------- Search ------------------------------- */

  var globalSearch = document.getElementById("globalSearch");
  var searchWrap = document.getElementById("searchWrap");
  var searchClear = document.getElementById("searchClear");

  function syncSearchState() {
    if (!globalSearch || !searchWrap) return;
    searchWrap.classList.toggle("has-value", globalSearch.value.length > 0);
  }

  if (globalSearch) {
    globalSearch.addEventListener("input", syncSearchState);
    syncSearchState();
  }

  if (searchClear) {
    searchClear.addEventListener("click", function () {
      if (!globalSearch) return;
      var hadValue = globalSearch.value.length > 0;
      globalSearch.value = "";
      syncSearchState();
      globalSearch.focus();
      if (hadValue && body.getAttribute("data-page") === "books") {
        var form = globalSearch.closest("form");
        if (form && typeof form.requestSubmit === "function") form.requestSubmit();
      }
    });
  }

  /* ---------------------------- Password toggle ------------------------- */

  $$("[data-password-toggle]").forEach(function (button) {
    button.addEventListener("click", function () {
      var selector = button.getAttribute("data-password-toggle");
      var input = selector ? document.querySelector(selector) : null;
      if (!input) return;

      var reveal = input.type === "password";
      input.type = reveal ? "text" : "password";
      button.classList.toggle("is-active", reveal);
      button.setAttribute("aria-label", reveal ? "Sembunyikan password" : "Tampilkan password");
      input.focus();
    });
  });

  /* ------------------------------ Keyboard ------------------------------ */

  function isTyping(target) {
    if (!target) return false;
    var tag = target.tagName;
    return tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT" || target.isContentEditable;
  }

  function modalIsOpen() {
    return Boolean(document.querySelector(".modal.show"));
  }

  document.addEventListener("keydown", function (event) {
    var typing = isTyping(event.target);

    if ((event.key === "/" && !typing && !modalIsOpen()) ||
        ((event.ctrlKey || event.metaKey) && String(event.key).toLowerCase() === "k" && !modalIsOpen())) {
      if (!globalSearch) return;
      event.preventDefault();
      globalSearch.focus();
      globalSearch.select();
      return;
    }

    if (event.key !== "Escape") return;

    if (globalSearch && document.activeElement === globalSearch) {
      if (globalSearch.value) {
        globalSearch.value = "";
        syncSearchState();
      } else {
        globalSearch.blur();
      }
      return;
    }

    closeSidebar();
  });

  /* -------------------------------- Init -------------------------------- */

  var initialModal = body.getAttribute("data-open-modal") || "";
  if (initialModal && MODAL_IDS[initialModal]) {
    bindFieldClearing(document.getElementById("bookForm"));
    bindFieldClearing(document.getElementById("memberForm"));
    bindFieldClearing(document.getElementById("loanForm"));

    window.setTimeout(function () {
      if (initialModal === "loan") updateLoanPreview();
      showModal(initialModal);
    }, 80);
  }

  // Ekspos API kecil untuk pemakaian lain bila diperlukan.
  window.BukuHub = {
    toast: toast,
    openModal: showModal,
    closeModal: hideModal,
    refreshNotifications: refreshNotifications
  };
})();
