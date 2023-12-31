define([], function () {
    'use strict';

    return {
        /**
         * Get local storage record by root name.
         * @param {string} root
         * @returns {*}
         */
        get: function (root) {
            const data = localStorage.getItem(root);

            try {
                return JSON.parse(data);
            } catch (e) {
                return data || null;
            }
        },

        /**
         * Set local storage record by root name.
         * @param {string} root
         * @param {*} data
         */
        set: function (root, data) {
            localStorage.setItem(root, JSON.stringify(data));
        },

        /**
         * Remove local storage record by root name.
         * @param {string} root
         */
        remove: function (root) {
            localStorage.removeItem(root);
        },
    };
});
