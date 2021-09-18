define([
    'jquery',
], function ($) {
    'use strict'

    class Playlist {
        _playlistConfig;

        _$playlist;
        _$playlistControl;

        /**
         * Player playlist constructor.
         * @param {jQuery} $context
         * @param {Object} playlistConfig
         */
        constructor($context, playlistConfig) {
            this._playlistConfig = playlistConfig;

            this._initFields($context);
            this._initBindings();
        }

        /**
         * Initialize playlist fields.
         * @param {jQuery} $context
         * @private
         */
        _initFields ($context) {
            this._$playlistControl = $('[data-playlist-control]', $context);
            this._$playlist = $('[data-playlist]', $context);
        }

        /**
         * Initialize playlist listeners.
         * @private
         */
        _initBindings () {
            this._$playlistControl.on('click', () => this.togglePlaylist());

            $(document).on('click', (event) => {
                if (!$(event.target).closest(this._$playlist).length) {
                    this.closePlaylist();
                }
            });

            $(document).on('keyup', (event) => this._handlePlaylistControls(event));
        }

        /**
         * Open/Close playlist menu according to its state.
         */
        togglePlaylist () {
            if (this._$playlist.hasClass('opened')) {
                this.closePlaylist();
            } else {
                this.openPlaylist();
            }
        }

        /**
         * Close playlist menu.
         */
        closePlaylist () {
            this._$playlist.removeClass('opened');
            this._$playlistControl.removeClass('active');
        }

        /**
         * Open playlist menu.
         */
        openPlaylist () {
            this._$playlist.addClass('opened');
            this._$playlistControl.addClass('active');
        }

        /**
         * Attach a callback on track selection.
         * Callable can accept these parameters:
         *      'id' - string containing file code
         *      'data' - object with all track data
         *      'event' - click event object
         * @param {function} callback
         */
        addSelectionCallback (callback) {
            $('[data-playlist-track]', this._$playlist).on('click', (event) => {
                let id = $(event.currentTarget).data('track-id');

                callback(id, this.getData(id), event);
            });
        }

        /**
         * Set playlist item as active by id.
         * @param {string} id
         */
        setActive (id) {
            this.clearActive();
            $('[data-track-id="' + id + '"]', this._$playlist).addClass('active');
        }

        /**
         * Reset playlist active items.
         */
        clearActive () {
            $('[data-playlist-track]', this._$playlist).removeClass('active');
        }

        /**
         * Retrieve track data by id and key.
         * Return all data if key is not specified
         * @param {string} id
         * @param {string} key
         * @returns {Object|null}
         */
        getData (id, key = '') {
            let data = this._playlistConfig[id] || null;

            if (data && key !== '') {
                data = data[key] || null;
            }

            return data;
        }

        /**
         * Handle playlist control buttons.
         * @param {Object} event
         * @private
         */
        _handlePlaylistControls (event) {
            switch (event.key) {
                case 'Escape':
                    this.closePlaylist();
                    break;
                case 'p':
                case 'з':
                    this.togglePlaylist();
                    break;
            }
        }
    }

    return {
        /**
         * Initialize player playlist with registered audio tracks.
         * @param {jQuery} $context
         * @param {Object} playlistConfig
         * @returns {Playlist}
         */
        init: function ($context, playlistConfig) {
            return new Playlist($context, playlistConfig);
        }
    }
});
