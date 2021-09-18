define([
    'jquery',
    'Awesome_Visualizer/js/playlist',
    'Awesome_Visualizer/js/visualizer',
    'translator',
    'jquery/ui',
], function ($, playlist, visualizer, __) {
    'use strict'

    const RUNNING_STATE = 'running';
    const PAUSED_STATE  = 'paused';
    const STOPPED_STATE = 'stopped';

    $.widget('awesome.player', {
        options: {
            playlistConfig: {},
            title: null,
        },

        $player: null,

        audio: null,
        $canvas: null,
        $playerControl: null,
        $time: null,
        $name: null,

        fileId: null,
        state: null,
        stopInterval: null,

        playlist: null,
        visualizer: null,

        /**
         * Constructor.
         */
        _create: function () {
            this._initFields();
            this.checkTouchScreen();
            this.updateCanvasSize();
            this._initBindings();
            this._initPlaylist();
        },

        /**
         * Init widget fields.
         * @private
         */
        _initFields: function () {
            this.$player = $('[data-player]', this.element);

            this.audio = $('[data-player-audio]', this.element).get(0);
            this.$canvas = $('[data-player-canvas]', this.element);
            this.$playerControl = $('[data-player-control]', this.element);
            this.$fullscreenControl = $('[data-player-fullscreen]', this.element);
            this.$time = $('[data-player-tracktime]', this.element);
            this.$name = $('[data-player-trackname]', this.element);
        },

        /**
         * Check if screen is touchable and apply respective changes.
         */
        checkTouchScreen: function () {
            if ('ontouchstart' in document.documentElement) {
                $(this.audio).addClass('visible');
            }
        },

        /**
         * Init widget event listeners.
         * @private
         */
        _initBindings: function () {
            $(window).on('resize', () => this.updateCanvasSize());

            $(document).on('dragover', (event) => {
                event.preventDefault();
                event.stopPropagation();
            });

            $(document).on('drop', (event) => {
                event.preventDefault();
                event.stopPropagation();

                let file = event.originalEvent.dataTransfer.files[0];

                this._initFile(file.name.replace(/\.[^/.]+$/, ''), URL.createObjectURL(file));

                this.audio.play();
            });

            $(this.audio).on('timeupdate', () => {
                let currentTime = this.audio.currentTime;

                this._updateTrackName(this.fileId, currentTime);
                this._updateTime(currentTime);
            });

            $(this.audio).on('play', () => {
                this.startVisualization();

                this.$playerControl.removeClass(['pause', 'active']).addClass('play');
                this.$playerControl.attr('title', __('Pause') + ' (Space)');
            });

            $(this.audio).on('pause', () => {
                this.stopVisualization();

                this.$playerControl.removeClass('play').addClass(['pause', 'active']);
                this.$playerControl.attr('title', __('Play') + ' (Space)');
            });

            $(this.$playerControl).on('click', () => {
                if (!this.audio.paused) {
                    this.audio.pause();
                } else {
                    this.audio.play();
                }
            });

            $(this.$fullscreenControl).on('click', () => this.toggleFullscreen());

            $(document).on('keydown', (event) => {
                this._handlePlayerControls(event);

                if ($('*:focus').length === 0 && this.fileId) {
                    this._handleAudioControls(event);
                }
            });
        },

        /**
         * Init player playlist.
         * @private
         */
        _initPlaylist: function () {
            this.playlist = playlist.init($(this.element), this.options.playlistConfig);

            this.playlist.addSelectionCallback((id, data) => {
                this._initFile(id, data.src, data);

                this.audio.play();
            });
        },

        /**
         * Initialize playing file.
         * @param {string} id
         * @param {string} src
         * @param {Object} data
         * @private
         */
        _initFile: function (id, src, data = {}) {
            this.fileId = id;
            $(this.audio).attr('src', src);

            let background = data.background || this.playlist.getData(id, 'background');
            this.$player.css('background-image', background ? `url(${background})` : '');
        },

        /**
         * Update audio track name.
         * Playlist is used according to the timeCode if possible.
         * @param {string} trackName
         * @param {number} timeCode
         * @private
         */
        _updateTrackName: function (trackName, timeCode) {
            if (this.options.playlistConfig[trackName]) {
                $.each(this.options.playlistConfig[trackName].playlist, (code, name) => {
                    if (code > timeCode) {
                        return false;
                    }

                    trackName = name;
                });
            }

            if (trackName !== this.$name.text()) {
                let oldTrackName = this.$name;

                this.$name = this.$name.clone().text(trackName);
                document.title = trackName + (this.options.title ? ' | ' + this.options.title : '');

                oldTrackName.parent().prepend(this.$name);
                this.$name.addClass('in');
                oldTrackName.addClass('out');

                setTimeout(() => {
                    oldTrackName.remove();
                    this.$name.removeClass('in');
                }, 300);
            }
        },

        /**
         * Format and update elapsed time.
         * @param {number} timeCode
         * @private
         */
        _updateTime: function (timeCode) {
            let hours   = ('00' + Math.floor(timeCode / 3600)).substr(-2);
            let minutes = ('00' + Math.floor(timeCode % 3600 / 60)).substr(-2);
            let seconds = ('00' + Math.floor(timeCode % 60)).substr(-2);

            this.$time.text(`${hours}:${minutes}:${seconds}`);
        },

        /**
         * Start/resume audio visualization.
         * Init visualizer if was not yet.
         */
        startVisualization: function () {
            if (this.state !== RUNNING_STATE) {
                if (!this.visualizer) {
                    this.visualizer = visualizer.init(this.audio, this.$canvas.get(0));
                    this.$playerControl.show();
                }

                this.state = RUNNING_STATE;
                this._run();
            }
        },

        /**
         * Call render and request next frame.
         * @private
         */
        _run: function () {
            this.visualizer.render();

            if (this.state !== STOPPED_STATE) {
                clearInterval(this.stopInterval);
                requestAnimationFrame(() => this._run());
            }
        },

        /**
         * Stop/Pause audio visualization.
         */
        stopVisualization: function () {
            this.state = PAUSED_STATE;

            this.stopInterval = setTimeout(() => {
                // Timeout is needed to have "fade" effect on canvas
                // Extra state is needed to solve goTo issue for audio element
                if (this.state === PAUSED_STATE) {
                    this.state = STOPPED_STATE;
                }
            }, 1000);
        },

        /**
         * Update canvas size attributes.
         */
        updateCanvasSize: function () {
            this.$canvas.attr('height', this.$canvas.height());
            this.$canvas.attr('width', this.$canvas.width());
        },

        /**
         * Handle player control buttons.
         * @param {Object} event
         * @private
         */
        _handlePlayerControls: function (event) {
            switch (event.key) {
                case 'f':
                case 'а':
                    this.toggleFullscreen();
                    // @TODO: Add hiding header/footer functionality, for Esc as well
                    break;
                case 'Escape':
                    this.playlist.togglePlaylist(false);
                    break;
                case 'l':
                case 'д':
                    // @TODO: Add layout change
                    break;
                case 'p':
                case 'з':
                    this.playlist.togglePlaylist();
                    break;
            }
        },

        /**
         * Handle player audio control buttons.
         * @param {Object} event
         * @private
         */
        _handleAudioControls: function (event) {
            switch (event.key) {
                case ' ':
                    if (!this.audio.paused) {
                        this.audio.pause();
                    } else {
                        this.audio.play();
                    }
                    break;
                case 'ArrowLeft':
                    this.audio.currentTime = Math.max(this.audio.currentTime - 10, 0);
                    break;
                case 'ArrowRight':
                    this.audio.currentTime = Math.min(this.audio.currentTime + 10, Math.floor(this.audio.duration));
                    break;
                case '0':
                    this.audio.currentTime = 0;
                    break;
                case 'ArrowUp':
                    this.audio.volume = Math.min(this.audio.volume + 0.1, 1);
                    break;
                case 'ArrowDown':
                    this.audio.volume = Math.max(this.audio.volume - 0.1, 0);
                    break;
                case 'm':
                case 'ь':
                    this.audio.muted = !this.audio.muted;
                    break;
            }
        },

        /**
         * Set or reset fullscreen mode.
         */
        toggleFullscreen: function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                this.$fullscreenControl.addClass('active');
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
                this.$fullscreenControl.removeClass('active');
            }
        },
    });
});
