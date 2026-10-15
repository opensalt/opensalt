// Page behaviors moved out of twig templates. Each initializer is
// self-guarding, so this file is safe to load on every page via the app entry.

// framework/ls_association/new: refresh the destination field and item list
// when the source document changes, and capture item-selection clicks
// (moved from framework/ls_association/new.html.twig).
(function () {
    const form = document.querySelector('form[name="ls_association"]');
    const lsDocSelect = document.getElementById('ls_association_lsDoc');
    if (!form || !lsDocSelect) {
        return;
    }

    function findDestinationField(doc) {
        return doc.getElementById('ls_association_destinationLsItem')
            || doc.getElementById('ls_association_destinationNodeUri');
    }

    lsDocSelect.addEventListener('change', async () => {
        const data = new FormData();
        data.append(lsDocSelect.name, lsDocSelect.value);

        const response = await fetch(form.action, { method: form.method, body: data });
        const newDoc = new DOMParser().parseFromString(await response.text(), 'text/html');

        const replacement = findDestinationField(newDoc);
        const current = findDestinationField(document);
        if (replacement && current) {
            current.replaceWith(replacement);
        }

        const newList = newDoc.getElementById('lsItemList');
        const currentList = document.getElementById('lsItemList');
        if (newList && currentList) {
            currentList.replaceWith(newList);
        }
    });

    const itemListWrap = document.getElementById('lsItemListWrap');
    if (itemListWrap) {
        itemListWrap.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();

            const markLink = e.target.closest('[name^="mark-"]');
            const destination = document.getElementById('ls_association_destinationLsItem');
            if (markLink && destination) {
                destination.value = markLink.getAttribute('name').replace('mark-', '');
            }
        });
    }
})();

// framework/ls_item/change_parent: clicking an item or doc in the
// change-parent list fills in the parent field (moved from
// framework/ls_item/change_parent.html.twig).
(function () {
    const itemListWrap = document.getElementById('lsItemListWrap-changeParent');
    const parentField = document.getElementById('ls_item_parent_parentItem');
    if (!itemListWrap || !parentField) {
        return;
    }

    itemListWrap.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();

        if (e.target.closest('.lsDoc')) {
            parentField.value = '';
            return;
        }

        const itemLink = e.target.closest('.lsItem');
        if (itemLink) {
            parentField.value = (itemLink.getAttribute('name') ?? '').replace('mark-', '');
        }
    });
})();

// user/token/created: readonly token inputs select their contents on click
// (replaces an inline onclick handler).
document.querySelectorAll('input[data-select-on-click]').forEach((input) => {
    input.addEventListener('click', () => input.select());
});
