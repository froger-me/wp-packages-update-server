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

    $('.webhook-multiple').each(function (idx, el) {
        el = $(el);

        var data = JSON.parse(el.find('.webhook-values').val());
        var urlNew = el.find('.new-webhook-item-url');
        var secretNew = el.find('.new-webhook-item-secret');
        var allEvents = el.find('input[data-webhook-event="all"]');
        var addButton = el.find('.webhook-add').get(0);
        var itemsContainer = el.find('.webhook-items').get(0);

        if ( 0 === data.length ) {
            data = {};
        }

        addButton.onclick = function () {
            addButton.disabled  = 'disabled';
            data[urlNew.val()] = {
                'secret': secretNew.val(),
                'events': []
            };

            el.find('.webhook-event-types input[type="checkbox"]').each(function (idx, checkbox) {
                checkbox = $(checkbox);

                if ('all' !== checkbox.data('webhook-event') && checkbox.prop('checked')) {
                    data[urlNew.val()].events.push(checkbox.data('webhook-event'));
                }
            });

            console.log(data);

            urlNew.val('');
            secretNew.val('');
            allEvents.prop('checked', true);
            allEvents.trigger('change');
            renderItems();
        };

        function validateInput() {
            var inputValue = urlNew.get(0).value.trim();
            var isEnabled = inputValue !== '' && !Object.values(data).some(function (item) {
                return item === inputValue;
            });
            var urlPattern = /^(https?:\/\/)?([\w-]+(\.[\w-]+)+\/?)|localhost(:\d+)?(\/[\w-]+)*\/?(\?\S*)?$/;

            isEnabled = isEnabled && urlPattern.test(inputValue) && (1 <= secretNew.val().length);
            addButton.disabled = !isEnabled;
        }

        function renderItems() {
            itemsContainer.innerHTML = '';

            Object.keys(data).forEach(function (index) {
                var itemContainer = document.createElement('div');
                var urlContainer = document.createElement('span');
                var urlText = document.createElement('span');
                var secretText = document.createElement('span');
                var eventsText = document.createElement('span');
                var deleteButton = document.createElement('button');

                itemContainer.className = 'item';
                urlText.textContent = index;
                urlText.title = index;
                urlText.classList = 'url';
                urlContainer.classList = 'url-container';
                secretText.textContent = data[index].secret;
                secretText.classList = 'secret';
                eventsText.textContent = '(' + data[index].events.join(', ') + ')';
                eventsText.classList = 'events';
                deleteButton.type = 'button';
                deleteButton.innerHTML = '<span class="wppus-remove-icon" aria-hidden="true"></span>';
                deleteButton.onclick = function () {

                    if (confirm(Wppus_l10n.deleteApiWebhookConfirm)) {
                        delete data[index];
                        renderItems();
                    }
                };

                urlContainer.appendChild(urlText);
                urlContainer.appendChild(eventsText);
                itemContainer.appendChild(urlContainer);
                itemContainer.appendChild(secretText);
                itemContainer.appendChild(deleteButton);
                itemsContainer.appendChild(itemContainer);
            });

            if ( 0 === Object.keys(data).length ) {
                itemsContainer.classList.add('empty');
            } else {
                itemsContainer.classList.remove('empty');
            }
        }

        urlNew.on('input', validateInput);
        secretNew.on('input', validateInput);
        el.find('.webhook-event-types input[type="checkbox"]').on('change', function () {

            if (allEvents.prop('checked')) {
                el.find('.webhook-event-types input[type="checkbox"]').prop('checked', true);
                el.find('.webhook-event-types input[type="checkbox"]').prop('disabled', true);
                allEvents.prop('disabled', false);
            } else {
                el.find('.webhook-event-types input[type="checkbox"]').prop('disabled', false);
            }
        });

        // Initial rendering
        allEvents.prop('checked', true);
        allEvents.trigger('change');
        renderItems();

        $('input[type="submit"]').on('click', function (e) {
            e.preventDefault();
            el.find('.webhook-values').val(JSON.stringify(data));
            $(this).closest('form').submit();
        });
    });

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

                    if (confirm(Wppus_l10n.deleteApiKeyConfirm)) {
                        delete data[index];
                        renderItems();
                    }
                };

                itemContainer.appendChild(nameText);
                itemContainer.appendChild(keyText);
                itemContainer.appendChild(deleteButton);
                itemsContainer.appendChild(itemContainer);
            });

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