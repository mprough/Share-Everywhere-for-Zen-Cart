(() => {
    'use strict';

    const container = document.getElementById('se-custom-links');
    const template = document.getElementById('se-custom-template');
    const add = document.getElementById('se-add-custom');
    let nextIndex = container.children.length;
    const limit = Number(container.dataset.limit);

    function updateLimit() {
        add.disabled = container.children.length >= limit;
    }

    add.addEventListener('click', () => {
        if (container.children.length >= limit) return;
        const row = template.content.cloneNode(true);
        row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace('__index__', String(nextIndex));
        });
        nextIndex += 1;
        const label = row.querySelector('input[name$="[label]"]');
        container.append(row);
        label.focus();
        updateLimit();
    });

    container.addEventListener('click', event => {
        const remove = event.target.closest('[data-se-remove]');
        if (!remove) return;
        remove.closest('[data-se-custom]').remove();
        updateLimit();
        add.focus();
    });
    updateLimit();
})();
