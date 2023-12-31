define([
    'jquery',
    'translator',
    'jquery/ui',
], function ($, __) {
    'use strict'

    $.widget('awesome.modal', {
        options: {
            autoOpen: false,
            autoOpenDelay: 300,
            buttons: [{
                attributes: {},
                class: 'button button--primary',
                text: __('Ok'),

                /**
                 * Action on button click.
                 */
                click: function () {
                    this.close();
                },
            }],
            closeOnEsc: true,
            closeOnOverlay: true,
            id: null,
            lockScroll: true,
            maxWidth: '640px',
            title: null,
        },

        /**
         * Initialized modal window.
         */
        $modal: null,

        /**
         * First and last focusable elements in modal window.
         */
        focusable: {
            $first: null,
            $last: null,
        },

        /**
         * Last active element before modal opening.
         */
        $lastActive: null,

        /**
         * Body scroll position before modal opening.
         */
        currentScrollPosition: 0,

        /**
         * Constructor.
         */
        _create: function () {
            this._initModal();
            this._initFields();
            this._initBindings();
            this._initModalState();
        },

        /**
         * Init event listeners.
         * @private
         */
        _initBindings: function () {
            $('[data-modal-trigger]', this.element).on('click', () => this.open());

            if (this.options.closeOnOverlay) {
                this.$modal.on('click', (event) => {
                    if (event.target === event.currentTarget) {
                        this.close();
                    }
                });
            }

            $('[data-modal-close]', this.$modal).on('click', () => this.close());
        },

        /**
         * Init modal window state.
         * @private
         */
        _initModalState: function () {
            $(document).ready(() => {
                if (this.options.autoOpen) {
                    setTimeout(() => this.open(), this.options.autoOpenDelay);
                } else if (this.options.id && window.location.hash) {
                    let matches = window.location.hash.match(/#(.*?)(?:\?|$)/);

                    if (matches[1] && matches[1] === this.options.id) {
                        setTimeout(() => this.open(), this.options.autoOpenDelay);
                    }
                }
            });
        },

        /**
         * Initialize modal window.
         * @private
         */
        _initModal: function () {
            let $window = $(`
<div class="modal__window" role="dialog" aria-modal="true">
    <button class="modal__close" type="button" title="${__('Close')}" data-modal-close></button>
</div>
`);

            if (this.options.buttons.length) {
                let $toolbar = $(`<div class="modal__toolbar"></div>`);

                $.each(this.options.buttons, (index, button) => {
                    let $button = $(`<button class="modal__button" type="button">${button.text}</button>`);

                    if (button.class) {
                        $button.addClass(button.class);
                    }

                    $.each(button.attributes, (name, value) => {
                        $button.attr(name, value);
                    });

                    if (button.click) {
                        $button.on('click', button.click.bind(this));
                    }

                    $toolbar.append($button);
                });

                $window.prepend($toolbar);
            }

            $window.prepend(this._getContent().clone().addClass('modal__content').show());

            if (this.options.title) {
                $window.attr('aria-label', this.options.title);
                $window.prepend(`<h2 class="modal__title">${this.options.title}</h2>`);
            }

            $window.css('max-width', this.options.maxWidth);

            this.$modal = $(`<div class="modal"></div>`);

            if (this.options.closeOnOverlay) {
                this.$modal.addClass('modal--backdrop');
            }

            this.$modal.append($window);

            this._getModalsWrapper().append(this.$modal);
        },

        /**
         * Return modal content element.
         * @returns {jQuery}
         * @private
         */
        _getContent: function () {
            return $('[data-modal-content]', this.element).length
                ? $('[data-modal-content]', this.element)
                : $(this.element);
        },

        /**
         * Prepare and return modal wrapper element.
         * @returns {jQuery}
         * @private
         */
        _getModalsWrapper: function () {
            let $modalsWrapper = $('[data-modals-wrapper]');

            if ($modalsWrapper.length === 0) {
                $modalsWrapper = $(`<div class="modals-wrapper" data-modals-wrapper></div>`);

                $('body').append($modalsWrapper);
            }

            return $modalsWrapper;
        },

        /**
         * Init widget fields.
         * Find and store first and last focusable elements.
         * @private
         */
        _initFields: function () {
            let $focusableElements = this.$modal.find('a:not([disabled]), :input:not([disabled]):not([type="hidden"])');

            this.focusable.$first = $focusableElements[0];
            this.focusable.$last  = $focusableElements[$focusableElements.length - 1];
        },

        /**
         * Open modal window.
         */
        open: function () {
            this.$modal.addClass('opened');

            $(document).on('keydown.modal.focusTrap', (event) => {
                if (event.key === 'Tab') {
                    if (event.shiftKey) {
                        if ($(document.activeElement).is(this.focusable.$first)) {
                            event.preventDefault();
                            this.focusable.$last.focus();
                        }
                    } else {
                        if ($(document.activeElement).is(this.focusable.$last)) {
                            event.preventDefault();
                            this.focusable.$first.focus();
                        }
                    }
                }
            });

            this.$lastActive = $(document.activeElement);
            setTimeout(() => this.focusable.$first.focus(), 400); // 400ms for slide animation to complete

            if (this.options.closeOnEsc) {
                $(document).on('keyup.modal.close', (event) => {
                    if (event.key === 'Escape') {
                        this.close();
                    }
                });
            }

            if (this.options.lockScroll) {
                this.currentScrollPosition = window.scrollY;

                $('body').css({
                    height:   '100%',
                    overflow: 'hidden',
                    position: 'fixed',
                    top:      `-${this.currentScrollPosition}px`,
                    width:    '100%',
                });
            }

            if (this.options.id) {
                history.replaceState('', document.title, window.location.pathname + window.location.search + `#${this.options.id}`);
            }
        },

        /**
         * Close modal window.
         */
        close: function () {
            this.$modal.removeClass('opened');

            $(document).off('keydown.modal.focusTrap');
            this.$lastActive.focus();

            if (this.options.closeOnEsc) {
                $(document).off('keyup.modal.close');
            }

            if (this.options.lockScroll) {
                $('body').css({
                    height:   '',
                    overflow: '',
                    position: '',
                    top:      '',
                    width:    '',
                });
                window.scrollTo(0, this.currentScrollPosition);
            }

            if (this.options.id) {
                history.replaceState('', document.title, window.location.pathname + window.location.search);
            }
        },
    });
});
