// modules.js — Placeholder rendering for pure frontend design

const modules = {
    init: function() {
        console.log("Modules ready (Design only mode).");
        this.renderPlaceholders();
    },

    renderPlaceholders: function() {
        // Find tables or lists and add a sample row as a design template
        const tables = document.querySelectorAll('tbody');
        tables.forEach(tbody => {
            if (tbody.children.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="10" class="text-center text-muted py-4">
                    <em>Backend pending. Use PHP to loop data here.</em>
                </td>`;
                tbody.appendChild(tr);
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => modules.init());
