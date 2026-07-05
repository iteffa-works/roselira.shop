(function () {
    var landing = document.querySelector('.landing[data-product]');
    if (!landing) {
        return;
    }

    var product;
    try {
        product = JSON.parse(landing.getAttribute('data-product') || '{}');
    } catch (error) {
        return;
    }

    var placeholder = landing.getAttribute('data-placeholder') || '/assets/img/placeholder.svg';
    var mainImage = landing.querySelector('.gallery__main-image');
    var mainVideo = landing.querySelector('.gallery__main-video');
    var thumbsWrap = landing.querySelector('[data-gallery-thumbs]');
    var thumbsTrack = landing.querySelector('.gallery__thumbs-track');
    var galleryCounter = landing.querySelector('[data-gallery-counter]');
    var galleryPrev = landing.querySelector('[data-gallery-prev]');
    var galleryNext = landing.querySelector('[data-gallery-next]');
    var variantNameEl = landing.querySelector('[data-variant-name]');
    var variantInput = landing.querySelector('[data-variant-input]');
    var priceBlock = landing.querySelector('[data-price-block]');
    var priceCurrent = landing.querySelector('[data-price-current]');
    var priceOld = landing.querySelector('[data-price-old]');
    var orderForm = landing.querySelector('[data-order-form]');
    var orderMessage = landing.querySelector('[data-order-message]');
    var orderSubmit = orderForm ? orderForm.querySelector('.order-form__submit') : null;
    var swatches = landing.querySelectorAll('.variant-swatch');
    var swatchesWrap = landing.querySelector('[data-variant-swatches]');
    var swatchesTrack = landing.querySelector('.variant-picker__track');
    var variantToggle = landing.querySelector('[data-variant-toggle]');
    var variantExpanded = false;
    var activeVariantId = product.default_variant;
    var activeImageIndex = 0;
    var galleryItems = [];

    function getVariant(id) {
        return (product.variants || []).find(function (variant) {
            return variant.id === id;
        });
    }

    function isVariantAvailable(variant) {
        return Boolean(variant) && variant.active !== false;
    }

    function findFirstAvailableVariantId() {
        var variants = product.variants || [];
        for (var i = 0; i < variants.length; i++) {
            if (isVariantAvailable(variants[i])) {
                return variants[i].id;
            }
        }

        return product.default_variant;
    }

    function buildGalleryItems(variant) {
        var items = [];

        (variant.images || []).forEach(function (src) {
            items.push({ type: 'image', src: src });
        });

        (variant.videos || []).forEach(function (video) {
            if (!video || !video.src) {
                return;
            }

            items.push({
                type: 'video',
                src: video.src,
                poster: video.poster || (variant.images && variant.images[0]) || placeholder,
            });
        });

        if (!items.length) {
            items.push({ type: 'image', src: placeholder });
        }

        return items;
    }

    function formatPrice(value, currency) {
        // Keep formatting rules in sync with PHP formatPrice() in flowaxy/Support/helpers.php.
        if (value === null || value === undefined || value === '') {
            return '';
        }

        var amount = Number(value);
        if (Number.isNaN(amount)) {
            return '';
        }

        if (currency === 'UAH') {
            return amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₴';
        }

        if (currency === 'USD') {
            return '$' + amount.toFixed(2);
        }

        if (currency === 'EUR') {
            return '€' + amount.toFixed(2);
        }

        return amount.toFixed(2) + ' ' + currency;
    }

    function pauseVideo() {
        if (!mainVideo) {
            return;
        }

        mainVideo.pause();
        mainVideo.removeAttribute('src');
        mainVideo.load();
        mainVideo.hidden = true;
    }

    function updateGalleryChrome() {
        var hasMany = galleryItems.length > 1;

        if (galleryCounter) {
            if (!hasMany) {
                galleryCounter.hidden = true;
            } else {
                galleryCounter.hidden = false;
                galleryCounter.textContent = String(activeImageIndex + 1) + ' / ' + String(galleryItems.length);
            }
        }

        if (galleryPrev) {
            galleryPrev.hidden = !hasMany;
        }

        if (galleryNext) {
            galleryNext.hidden = !hasMany;
        }
    }

    function setActiveThumb(index) {
        if (!thumbsWrap) {
            return;
        }

        thumbsWrap.querySelectorAll('.gallery__thumb').forEach(function (thumb, thumbIndex) {
            var isActive = thumbIndex === index;
            thumb.classList.toggle('is-active', isActive);
            thumb.setAttribute('aria-current', isActive ? 'true' : 'false');
        });

        scrollActiveThumbIntoView();
        updateThumbTrackFade();
    }

    function updateThumbTrackFade() {
        if (!thumbsTrack || !thumbsWrap || thumbsWrap.classList.contains('is-hidden')) {
            if (thumbsTrack) {
                thumbsTrack.classList.remove('is-scrollable', 'is-scrolled-start', 'is-scrolled-end');
            }
            return;
        }

        var maxScroll = thumbsWrap.scrollWidth - thumbsWrap.clientWidth;
        var canScroll = maxScroll > 4;

        thumbsTrack.classList.toggle('is-scrollable', canScroll);
        thumbsTrack.classList.toggle('is-scrolled-start', thumbsWrap.scrollLeft <= 4);
        thumbsTrack.classList.toggle('is-scrolled-end', thumbsWrap.scrollLeft >= maxScroll - 4);
    }

    function scrollActiveThumbIntoView() {
        if (!thumbsWrap || thumbsWrap.classList.contains('is-hidden')) {
            return;
        }

        var activeThumb = thumbsWrap.querySelector('.gallery__thumb.is-active');
        if (activeThumb && typeof activeThumb.scrollIntoView === 'function') {
            activeThumb.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'smooth' });
        }
    }

    function showGalleryItem(index) {
        if (!galleryItems.length) {
            return;
        }

        if (index < 0) {
            index = galleryItems.length - 1;
        } else if (index >= galleryItems.length) {
            index = 0;
        }

        var item = galleryItems[index];
        if (!item) {
            return;
        }

        activeImageIndex = index;
        updateGalleryChrome();
        setActiveThumb(index);

        if (item.type === 'video' && mainVideo) {
            if (mainImage) {
                mainImage.hidden = true;
            }

            mainVideo.hidden = false;

            if (item.poster) {
                mainVideo.poster = item.poster;
            }

            if (mainVideo.dataset.loadedSrc !== item.src) {
                mainVideo.dataset.loadedSrc = item.src;
                mainVideo.src = item.src;
                mainVideo.load();
            }

            mainVideo.play().catch(function () {});
            return;
        }

        pauseVideo();

        if (mainImage) {
            mainImage.hidden = false;
            mainImage.src = item.src;
            mainImage.onerror = function () {
                mainImage.src = placeholder;
            };
        }
    }

    function renderGallery(variant) {
        galleryItems = buildGalleryItems(variant || {});
        activeImageIndex = 0;

        if (!thumbsWrap) {
            showGalleryItem(0);
            return;
        }

        thumbsWrap.innerHTML = '';

        if (galleryItems.length <= 1) {
            thumbsWrap.classList.add('is-hidden');
            showGalleryItem(0);
            updateThumbTrackFade();
            return;
        }

        thumbsWrap.classList.remove('is-hidden');

        galleryItems.forEach(function (item, index) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'gallery__thumb' + (index === 0 ? ' is-active' : '');

            if (item.type === 'video') {
                button.classList.add('gallery__thumb--video');
            }

            button.setAttribute('data-index', String(index));
            button.setAttribute('aria-label', item.type === 'video' ? 'Video ' + String(index + 1) : 'Photo ' + String(index + 1));
            button.setAttribute('aria-current', index === 0 ? 'true' : 'false');

            var image = document.createElement('img');
            image.loading = 'lazy';
            image.src = item.type === 'video' ? (item.poster || placeholder) : item.src;
            image.alt = '';
            image.onerror = function () {
                image.src = placeholder;
            };

            button.appendChild(image);

            if (item.type === 'video') {
                var play = document.createElement('span');
                play.className = 'gallery__thumb-play';
                play.setAttribute('aria-hidden', 'true');
                button.appendChild(play);
            }

            button.addEventListener('click', function () {
                showGalleryItem(index);
            });

            thumbsWrap.appendChild(button);
        });

        showGalleryItem(0);
        updateThumbTrackFade();
    }

    function updatePrice(variant) {
        if (!priceBlock || !priceCurrent) {
            return;
        }

        var price = variant.price !== null && variant.price !== undefined ? variant.price : product.price;
        var priceOldValue = variant.price_old !== null && variant.price_old !== undefined ? variant.price_old : product.price_old;
        var currency = product.price_currency || 'USD';
        var currentText = formatPrice(price, currency);

        if (!currentText) {
            priceBlock.classList.add('is-hidden');
            return;
        }

        priceBlock.classList.remove('is-hidden');
        priceCurrent.textContent = currentText;

        if (priceOld) {
            var oldText = formatPrice(priceOldValue, currency);
            priceOld.textContent = oldText;
            priceOld.classList.toggle('is-hidden', !oldText);
        }
    }

    function updateSwatchTrackFade() {
        if (!swatchesTrack || !swatchesWrap || variantExpanded) {
            if (swatchesTrack) {
                swatchesTrack.classList.remove('is-scrollable', 'is-scrolled-start', 'is-scrolled-end');
            }
            return;
        }

        var maxScroll = swatchesWrap.scrollWidth - swatchesWrap.clientWidth;
        var canScroll = maxScroll > 4;

        swatchesTrack.classList.toggle('is-scrollable', canScroll);
        swatchesTrack.classList.toggle('is-scrolled-start', swatchesWrap.scrollLeft <= 4);
        swatchesTrack.classList.toggle('is-scrolled-end', swatchesWrap.scrollLeft >= maxScroll - 4);
    }

    function scrollActiveSwatchIntoView() {
        if (!swatchesWrap || variantExpanded) {
            return;
        }

        var activeSwatch = swatchesWrap.querySelector('.variant-swatch.is-active');
        if (activeSwatch && typeof activeSwatch.scrollIntoView === 'function') {
            activeSwatch.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'smooth' });
        }

        updateSwatchTrackFade();
    }

    function setVariantPickerExpanded(expanded) {
        if (!swatchesWrap) {
            return;
        }

        variantExpanded = expanded;
        swatchesWrap.classList.toggle('is-collapsed', !expanded);
        swatchesWrap.classList.toggle('is-expanded', expanded);

        if (variantToggle) {
            variantToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            variantToggle.textContent = expanded
                ? (variantToggle.getAttribute('data-label-collapse') || '')
                : (variantToggle.getAttribute('data-label-expand') || '');
        }

        if (!expanded) {
            scrollActiveSwatchIntoView();
        } else {
            updateSwatchTrackFade();
        }
    }

    function updateOrderState(variant) {
        var available = isVariantAvailable(variant);

        if (variantInput) {
            variantInput.value = available ? (variant.id || '') : '';
        }

        if (orderSubmit) {
            orderSubmit.disabled = !available;
        }
    }

    function selectVariant(id) {
        var variant = getVariant(id);
        if (!variant) {
            return;
        }

        activeVariantId = id;

        if (variantNameEl) {
            variantNameEl.textContent = variant.name || id;
        }

        swatches.forEach(function (swatch) {
            var isActive = swatch.getAttribute('data-variant-id') === id;
            swatch.classList.toggle('is-active', isActive);
            swatch.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        renderGallery(variant);
        updatePrice(variant);
        updateOrderState(variant);
        scrollActiveSwatchIntoView();
    }

    if (variantToggle) {
        variantToggle.addEventListener('click', function () {
            setVariantPickerExpanded(!variantExpanded);
        });
    }

    if (swatchesWrap) {
        swatchesWrap.addEventListener('scroll', updateSwatchTrackFade, { passive: true });
    }

    if (thumbsWrap) {
        thumbsWrap.addEventListener('scroll', updateThumbTrackFade, { passive: true });
    }

    window.addEventListener('resize', function () {
        updateSwatchTrackFade();
        updateThumbTrackFade();
    });

    swatches.forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            selectVariant(swatch.getAttribute('data-variant-id') || '');
        });
    });

    if (galleryPrev) {
        galleryPrev.addEventListener('click', function () {
            showGalleryItem(activeImageIndex - 1);
        });
    }

    if (galleryNext) {
        galleryNext.addEventListener('click', function () {
            showGalleryItem(activeImageIndex + 1);
        });
    }

    var galleryMain = landing.querySelector('[data-gallery-main]');
    if (galleryMain) {
        galleryMain.addEventListener('keydown', function (event) {
            if (galleryItems.length <= 1) {
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showGalleryItem(activeImageIndex - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                showGalleryItem(activeImageIndex + 1);
            }
        });
    }

    if (orderForm) {
        orderForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (orderMessage) {
                orderMessage.hidden = true;
                orderMessage.className = 'order-form__message';
            }

            var previewVariant = getVariant(activeVariantId);
            if (!isVariantAvailable(previewVariant)) {
                return;
            }

            if (orderForm.querySelector('[data-recaptcha-sitekey]')) {
                var captchaField = orderForm.querySelector('[name="g-recaptcha-response"]');
                if (!captchaField || !captchaField.value) {
                    if (orderMessage) {
                        orderMessage.hidden = false;
                        orderMessage.classList.add('is-error');
                        orderMessage.textContent = orderForm.getAttribute('data-error-captcha') || 'Confirm captcha';
                    }
                    return;
                }
            }

            var submitButton = orderForm.querySelector('.order-form__submit');
            if (submitButton) {
                submitButton.disabled = true;
            }

            fetch(orderForm.action, {
                method: 'POST',
                body: new FormData(orderForm),
                headers: {
                    Accept: 'application/json',
                },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!orderMessage) {
                        return;
                    }

                    orderMessage.hidden = false;
                    orderMessage.textContent = result.data.message || '';
                    orderMessage.classList.add(result.ok ? 'is-success' : 'is-error');

                    if (result.ok) {
                        orderForm.reset();
                        if (variantInput && isVariantAvailable(getVariant(activeVariantId))) {
                            variantInput.value = activeVariantId;
                        }
                        if (window.FlowaxyAnalytics && window.FlowaxyConsent && window.FlowaxyConsent.hasAnalytics()) {
                            var productData = null;
                            try {
                                productData = JSON.parse(landing.getAttribute('data-product') || '{}');
                            } catch (error) {
                                productData = null;
                            }
                            window.FlowaxyAnalytics.trackLead({
                                product_id: productData && productData.slug ? productData.slug : '',
                                product_name: productData && landing.querySelector('.landing__title')
                                    ? landing.querySelector('.landing__title').textContent
                                    : '',
                                value: productData && productData.price ? productData.price : 0,
                                currency: productData && productData.price_currency ? productData.price_currency : 'UAH',
                            });
                        }
                    }
                })
                .catch(function () {
                    if (orderMessage) {
                        orderMessage.hidden = false;
                        orderMessage.classList.add('is-error');
                        orderMessage.textContent = orderForm.getAttribute('data-error-network') || 'Error';
                    }
                })
                .finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    if (window.grecaptcha && orderForm.querySelector('[data-recaptcha-sitekey]')) {
                        grecaptcha.reset();
                    }
                });
        });
    }

    var initialVariantId = product.default_variant;
    if (!isVariantAvailable(getVariant(initialVariantId))) {
        initialVariantId = findFirstAvailableVariantId();
    }

    selectVariant(initialVariantId);
    updateSwatchTrackFade();

    document.querySelectorAll('[data-product-tabs]').forEach(function (root) {
        var tabs = root.querySelectorAll('.product-tabs__tab');
        var panels = root.querySelectorAll('.product-tabs__panel');

        function activateTab(tab) {
            var section = tab.getAttribute('data-section');
            if (!section) {
                return;
            }

            tabs.forEach(function (item) {
                var isActive = item === tab;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panels.forEach(function (panel) {
                var isActive = panel.getAttribute('data-section') === section;
                panel.classList.toggle('is-active', isActive);
                panel.hidden = !isActive;
            });
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () {
                activateTab(tab);
            });

            tab.addEventListener('keydown', function (event) {
                var nextIndex = index;

                if (event.key === 'ArrowRight') {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = tabs.length - 1;
                } else {
                    return;
                }

                event.preventDefault();
                tabs[nextIndex].focus();
                activateTab(tabs[nextIndex]);
            });
        });
    });
})();
