define([], function () {

    window.ProgressBar = Class.create();
    ProgressBar.prototype = {

        // ---------------------------------------

        initialize: function (progressBarId) {
            if (typeof progressBarId == 'undefined') {
                progressBarId = '';
            }

            this.progressBarId = progressBarId;

            this.title = '';
            this.percents = 0;
            this.status = '';

            this.hide();
            this.makeAndFillHtml();
            this.reset();
        },

        // ---------------------------------------

        makeAndFillHtml: function () {
            if ($$('#' + this.progressBarId).length == 0) {
                return false;
            }

            if (!$(this.progressBarId).hasClassName('progress_bar_container')) {
                $(this.progressBarId).addClassName('progress_bar_container');
            }

            var html = ' <div id="' + this.progressBarId + '_title" class="progress_bar_title"></div>' +
                ' <div id="' + this.progressBarId + '_control" class="progress_bar_control">' +
                '     <div id="' + this.progressBarId + '_fill" class="progress_bar_fill">&nbsp;</div>' +
                '     <div id="' + this.progressBarId + '_percents" class="progress_bar_percents"></div>' +
                ' </div>' +
                ' <div id="' + this.progressBarId + '_status" class="progress_bar_status"></div>';

            $(this.progressBarId).innerHTML = html;
        },

        reset: function () {
            this.setTitle('');
            this.setPercents(0, 0);
            this.setStatus('');
        },

        // ---------------------------------------

        show: function (title, percents, status) {
            if (typeof title == 'undefined') {
                title = this.title;
            }

            if (typeof percents == 'undefined') {
                percents = this.percents;
            }

            percents = parseInt(percents);

            if (typeof status == 'undefined') {
                status = this.status;
            }

            this.setTitle(title);
            this.setPercents(percents, 0);
            this.setStatus(status);

            $(this.progressBarId).show();
        },

        hide: function () {
            $(this.progressBarId).hide();
        },

        // ---------------------------------------

        getTitle: function () {
            return this.title;
        },

        setTitle: function (string) {
            if (typeof string == 'undefined') {
                string = '&nbsp;';
            }

            this.title = string;

            $(this.progressBarId + '_title').innerHTML = this.title;
        },

        // ---------------------------------------

        getPercents: function () {
            return this.percents;
        },

        setPercents: function (value, animation) {
            this.animationWorking = false;

            if (typeof value == 'undefined') {
                value = 0;
            }

            value = parseInt(value);

            if (value < 0) {
                value = 0;
            }
            if (value > 100) {
                value = 100;
            }

            if (typeof animation == 'undefined') {
                animation = 1;
            }

            animation = parseInt(animation);

            if (value == 0 || value == 100) {
                animation = 0;
            }

            if (value != 0 && value < this.percents) {
                animation = 0;
            }

            if (animation == 1) {
                this.animationWorking = true;
                this.animatePercents(value);
            } else {
                this.percents = value;
                this.setHtmlPercents();
            }
        },

        animatePercents: function (needleValue) {
            if (!this.animationWorking) {
                return;
            }

            if (this.percents == needleValue) {
                this.setHtmlPercents();
                return;
            }

            var interval = 0;
            if (needleValue > this.percents) {
                this.percents++;
                interval = needleValue - this.percents;
            } else {
                this.percents--;
                interval = this.percents - needleValue;
            }

            this.setHtmlPercents();

            var speed = 80;

            if (interval < 70) {
                speed = speed - interval;
            } else {
                speed = 10;
            }

            var self = this;
            setTimeout(function () {
                self.animatePercents(needleValue);
            }, speed);
        },

        setHtmlPercents: function () {
            $(this.progressBarId + '_fill').setStyle({width: this.percents + '%'});

            if (this.percents < 53) {
                if ($(this.progressBarId + '_percents').hasClassName('progress_bar_percents_after_50')) {
                    $(this.progressBarId + '_percents').removeClassName('progress_bar_percents_after_50');
                }
                if (!$(this.progressBarId + '_percents').hasClassName('progress_bar_percents_before_50')) {
                    $(this.progressBarId + '_percents').addClassName('progress_bar_percents_before_50');
                }
            } else {
                if ($(this.progressBarId + '_percents').hasClassName('progress_bar_percents_before_50')) {
                    $(this.progressBarId + '_percents').removeClassName('progress_bar_percents_before_50');
                }
                if (!$(this.progressBarId + '_percents').hasClassName('progress_bar_percents_after_50')) {
                    $(this.progressBarId + '_percents').addClassName('progress_bar_percents_after_50');
                }
            }

            $(this.progressBarId + '_percents').innerHTML = this.percents + '&nbsp;%';
        },

        // ---------------------------------------

        getStatus: function () {
            return this.status;
        },

        setStatus: function (string) {
            if (typeof string == 'undefined') {
                string = '&nbsp;';
            }

            this.status = string;

            $(this.progressBarId + '_status').innerHTML = this.status;
        }

        // ---------------------------------------
    };
});