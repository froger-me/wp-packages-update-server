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
        var licenseAPIKeyNew = el.find('.new-webhook-item-license_api_key');
        var secretNew = el.find('.new-webhook-item-secret');
        var allEvents = el.find('input[data-webhook-event="all"]');
        var packageEvents = el.find('input[data-webhook-event="package"]');
        var licenseEvents = el.find('input[data-webhook-event="license"]');
        var addButton = el.find('.webhook-add').get(0);
        var itemsContainer = el.find('.webhook-items').get(0);

        if ( 0 === data.length ) {
            data = {};
        }

        addButton.onclick = function () {
            addButton.disabled = 'disabled';

            if (
                el.find('.event-container.license input[type="checkbox"]:checked').length &&
                !licenseAPIKeyNew.val().length
            ) {
                if (!confirm(WppusAdminMain_l10n.addWebhookNoLicenseApiConfirm)) {
                    addButton.disabled = false;
                    renderItems();

                    return;
                }
            }

            data[urlNew.val()] = {
                'secret': secretNew.val(),
                'licenseAPIKey': licenseAPIKeyNew.val(),
                'events': []
            };

            if (packageEvents.prop('checked')) {
                packageEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', false);
            }

            if (licenseEvents.prop('checked')) {
                licenseEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', false);
            }

            if (packageEvents.closest('.event-container').find('.child input[type="checkbox"]').length === packageEvents.closest('.event-container').find('.child input[type="checkbox"]:checked').length) {
                packageEvents.prop('checked', true);
                packageEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', false);
            }

            if (licenseEvents.closest('.event-container').find('.child input[type="checkbox"]').length === licenseEvents.closest('.event-container').find('.child input[type="checkbox"]:checked').length) {
                licenseEvents.prop('checked', true);
                licenseEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', false);
            }

            el.find('.event-types input[type="checkbox"]').each(function (idx, checkbox) {
                checkbox = $(checkbox);

                if ('all' !== checkbox.data('webhook-event') && checkbox.prop('checked')) {
                    data[urlNew.val()].events.push(checkbox.data('webhook-event'));
                }
            });

            urlNew.val('');
            secretNew.val('');
            licenseAPIKeyNew.val('');
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
            var apiKeyPattern = /^L\*\*[a-zA-Z0-9_-]+$/;

            isEnabled = isEnabled && urlPattern.test(inputValue) && (16 <= secretNew.val().length) && 0 !== el.find('.event-container input[type="checkbox"]:checked').length && (0 === licenseAPIKeyNew.val().length || apiKeyPattern.test(licenseAPIKeyNew.val()));
            addButton.disabled = !isEnabled;
        }

        function renderItems() {
            itemsContainer.innerHTML = '';

            Object.keys(data).forEach(function (index) {
                var itemContainer = document.createElement('div');
                var urlText = document.createElement('span');
                var secretText = document.createElement('span');
                var eventsText = document.createElement('span');
                var deleteButton = document.createElement('button');
                var message = '';
                var events = data[index].events;

                if (2 === events.length && events.includes('package') && events.includes('license')) {
                     message = WppusAdminMain_l10n.eventApiCountAll;
                } else {
                    var messageParts = { package: '', license: '' };

                    ['package', 'license'].forEach(function (val, index) {
                        var type = ('package' === val) ? WppusAdminMain_l10n.eventApiTypePackage : WppusAdminMain_l10n.eventApiTypeLicense;

                        if (events.includes(val)) {
                            messageParts[val] = WppusAdminMain_l10n.eventApiCountAllType.replace('%s', type);
                        } else if (1 === events.filter(function (i) { return i.startsWith(val); }).length) {
                            messageParts[val] = WppusAdminMain_l10n.eventApiCountTypeSingular.replace('%s', type);
                        } else if (events.filter(function (i) { return i.startsWith(val); }).length) {
                            messageParts[val] = WppusAdminMain_l10n.eventApiCountTypePlural.replace('%1$d', events.filter(function (i) { return i.startsWith(val); }).length).replace('%2$s', type);
                        }
                    });

                    if ('' !== messageParts.package && '' !== messageParts.license) {
                        message = messageParts.package + WppusAdminMain_l10n.apiSumSep + '<br>' + messageParts.license;
                    } else if ( '' !== messageParts.package ) {
                        message = messageParts.package;
                    } else {
                        message = messageParts.license;
                    }
                }

                itemContainer.className = 'item';
                urlText.textContent = index;
                urlText.title = index;
                urlText.classList = 'url';
                secretText.textContent = data[index].secret;
                secretText.classList = 'secret';
                eventsText.innerHTML = message;
                eventsText.title = '(' + data[index].events.join(', ') + ')';
                eventsText.classList = 'summary';
                deleteButton.type = 'button';
                deleteButton.innerHTML = '<span class="wppus-remove-icon" aria-hidden="true"></span>';
                deleteButton.onclick = function () {

                    if (confirm(WppusAdminMain_l10n.deleteApiWebhookConfirm)) {
                        delete data[index];
                        renderItems();
                    }
                };

                if (data[index].licenseAPIKey) {
                    urlText.title += "\n(" + data[index].licenseAPIKey + ')';
                }

                itemContainer.appendChild(eventsText);
                itemContainer.appendChild(urlText);
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
        licenseAPIKeyNew.on('input', validateInput);
        el.find('.event-types input[type="checkbox"]').on('change', function () {

            if (allEvents.prop('checked')) {
                el.find('.event-types input[type="checkbox"]').prop('checked', true);
                el.find('.event-types input[type="checkbox"]').prop('disabled', true);
                allEvents.prop('disabled', false);
            } else {
                el.find('.event-types input[type="checkbox"]').prop('disabled', false);

                if (packageEvents.prop('checked')) {
                    packageEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', true);
                    packageEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('disabled', true);
                }

                if (licenseEvents.prop('checked')) {
                    licenseEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('checked', true);
                    licenseEvents.closest('.event-container').find('.child input[type="checkbox"]').prop('disabled', true);
                }
            }

            if (el.find('.event-container.license input[type="checkbox"]:checked').length) {
                el.find('.show-if-license').removeClass('hidden');
            } else {
                el.find('.show-if-license').addClass('hidden');
            }

            validateInput();
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
        var idNew = el.find('.new-api-key-item-id');
        var allActions = el.find('input[data-api-action="all"]');
        var addButton = el.find('.api-keys-add').get(0);
        var itemsContainer = el.find('.api-keys-items').get(0);

        if ( 0 === data.length ) {
            data = {};
        }

        addButton.onclick = function () {
            addButton.disabled = 'disabled';
            data[idNew.val()] = {
                'key': bin2hex_openssl_random_pseudo_bytes(16),
                'access': []
            };

            if (allActions.prop('checked') || el.find('.event-container:not(.all, .other) input[type="checkbox"]').length === el.find('.event-container:not(.all, .other) input[type="checkbox"]:checked').length) {
                allActions.prop('checked', true);
                el.find('.event-container:not(.all, .other) input[type="checkbox"]').prop('checked', false);
                data[idNew.val()].access.push('all');
            }

            el.find('.event-container input[type="checkbox"]').each(function (idx, checkbox) {
                checkbox = $(checkbox);

                if ('all' !== checkbox.data('api-action') && checkbox.prop('checked')) {
                    data[idNew.val()].access.push(checkbox.data('api-action'));
                }
            });

            el.find('.event-container.other input[type="checkbox"]').prop('checked', false);
            allActions.prop('checked', true);
            allActions.trigger('change');
            idNew.val('');
            validateInput();
            renderItems();
        };

        function validateInput() {
            var inputValue = idNew.get(0).value.trim();
            var isEnabled = inputValue !== '' && !Object.values(data).some(function (item) {
                return item === inputValue;
            });

            isEnabled = isEnabled && /^[a-zA-Z0-9_-]+$/.test(inputValue) && 0 !== el.find('.event-container:not(.other) input[type="checkbox"]:checked').length;

            addButton.disabled = !isEnabled;
        }

        function renderItems() {
            itemsContainer.innerHTML = '';

            Object.keys(data).forEach(function (index) {
                var itemContainer = document.createElement('div');
                var idText = document.createElement('span');
                var keyText = document.createElement('span');
                var actionsText = document.createElement('span');
                var deleteButton = document.createElement('button');
                var message = '';
                var access = data[index].access;

                if (1 === access.length && 'all' === access[0]) {
                    message = WppusAdminMain_l10n.actionApiCountAll;
                } else if (2 === access.length && access.includes('all') && access.includes('other')) {
                    message = WppusAdminMain_l10n.actionApiCountAllOther;
                } else if ( 2 === access.length && access.includes('other') ) {
                    message = WppusAdminMain_l10n.actionApiCountSingularOther;
                } else if ( access.includes('other') ) {
                    message = WppusAdminMain_l10n.actionApiCountPluralOther.replace('%d', access.length - 1);
                } else if (1 === access.length) {
                    message = WppusAdminMain_l10n.actionApiCountSingular;
                } else {
                    message = WppusAdminMain_l10n.actionApiCountPlural.replace('%d', access.length);
                }

                itemContainer.className = 'item';
                idText.textContent = index;
                idText.classList = 'id';
                keyText.textContent = data[index].key;
                keyText.classList = 'key';
                actionsText.textContent = message;
                actionsText.title = '(' + data[index].access.join(', ') + ')';
                actionsText.classList = 'summary';
                deleteButton.type = 'button';
                deleteButton.innerHTML = '<span class="wppus-remove-icon" aria-hidden="true"></span>';
                deleteButton.onclick = function () {

                    if (confirm(WppusAdminMain_l10n.deleteApiKeyConfirm)) {
                        delete data[index];
                        renderItems();
                    }
                };

                itemContainer.appendChild(actionsText);
                itemContainer.appendChild(idText);
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

        idNew.on('input', validateInput);
        el.find('.event-types input[type="checkbox"]').on('change', function () {

            if (allActions.prop('checked')) {
                el.find('.event-container:not(.other) input[type="checkbox"]').prop('checked', true);
                el.find('.event-container:not(.other) input[type="checkbox"]').prop('disabled', true);
                allActions.prop('disabled', false);
            } else {
                el.find('.event-types input[type="checkbox"]').prop('disabled', false);
            }

            validateInput();
        });

        // Initial rendering
        allActions.prop('checked', true);
        allActions.trigger('change');
        renderItems();

        $('input[type="submit"]').on('click', function (e) {
            e.preventDefault();
            el.find('.api-key-values').val(JSON.stringify(data));
            $(this).closest('form').submit();
        });
    });

});