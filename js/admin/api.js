jQuery(document).ready(function ($) {

    function bin2hex_openssl_random_pseudo_bytes(length) {
        var array = new Uint8Array(length);
        var hexString = '';

        window.crypto.getRandomValues(array);

        for (var i = 0; i < array.length; i++) {
            hexString += array[i].toString(length).padStart(2, '0');
        }

        return hexString;
    }

    $('.api-keys-multiple').each(function (idx, el) {
        el = $(el);

        var data = JSON.parse(el.find('.api-key-values').val());
        var nameNew = el.find('.new-api-key-item-name');
        var addButton = el.find('.api-keys-add').get(0);
        var itemsContainer = el.find('.api-keys-items').get(0);

        if ( 0 === data.length ) {
            data = {};
        }

        addButton.onclick = function () {
            addButton.disabled  = 'disabled';
            data[bin2hex_openssl_random_pseudo_bytes(16)] = nameNew.val();

            nameNew.val('');
            renderItems();
        };

        function renderItems() {
            itemsContainer.innerHTML = '';

            Object.keys(data).forEach(function (index) {
                var itemContainer = document.createElement('div');
                var nameText = document.createElement('span');
                var keyText = document.createElement('span');
                var deleteButton = document.createElement('button');

                itemContainer.className = 'item';
                nameText.textContent = data[index];
                nameText.classList = 'name';
                keyText.textContent = index;
                keyText.classList = 'key';
                deleteButton.type = 'button';
                deleteButton.innerHTML = '<span class="wppus-remove-icon" aria-hidden="true"></span>';
                deleteButton.onclick = function () {

                    if (confirm(Wppus_l10n.deleteApiConfirm)) {
                        delete data[index];
                        renderItems();
                    }
                };

                itemContainer.appendChild(nameText);
                itemContainer.appendChild(keyText);
                itemContainer.appendChild(deleteButton);
                itemsContainer.appendChild(itemContainer);
            });

            console.log(Object.keys(data).length);
            if ( 0 === Object.keys(data).length ) {
                itemsContainer.classList.add('empty');
            } else {
                itemsContainer.classList.remove('empty');
            }
        }

        nameNew.on('input', function () {
            var inputValue = $(this).get(0).value.trim();
            var isEnabled = inputValue !== '' && !Object.values(data).some(function (item) {
                return item === inputValue;
            });

            addButton.disabled = !isEnabled;
        });

        // Initial rendering
        renderItems();

        $('input[type="submit"]').on('click', function (e) {
            e.preventDefault();
            el.find('.api-key-values').val(JSON.stringify(data));
            $(this).closest('form').submit();
        });
    });

});