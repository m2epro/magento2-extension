define([
    'jquery',
    'prototype'
], function ($) {
    'use strict';

    window.CronTab = Class.create({
        scenarioQueue: [],

        /**
         * @param {Object} options
         * @param {string} options.cronRunUrl
         * @param {string} options.moduleIdentifier
         */
        initialize: function (options) {
            this.cronRunUrl = options.cronRunUrl;
            this.localstorageKey = options.moduleIdentifier + '::cronTaskScenario';

            this.dropDownTrigger = $('#dropdown-trigger');
            this.dropDownMenu = $('#dropdown-menu');
            this.dropDownTriggerText = this.dropDownTrigger.find('.trigger-text');

            this.runCronButton = $('#run_task');
            this.addToSequenceButton = $('#add_to_queue');

            this.runScenarioButton = $('#run-scenario');
            this.clearQueueButton = $('#clear-queue');
            this.queueList = $('#queue_list');

            this.initMainEvents();
            this.initDragAndDropEvents();
            this.loadScenarioQueue();

            this.initDropDownEvents();
            $(this.dropDownMenu).find('.dropdown-item:first').trigger('click');
        },

        initMainEvents: function () {
            this.runCronButton.on('click', () => {
                const task = $('#dropdown-menu .selected').data('value');
                this.runTasks([task])
            });

            this.addToSequenceButton.on('click', ()=> {
                const selectedOption = $('#dropdown-menu .selected');
                if (!selectedOption.data('value')) {
                    return;
                }

                this.scenarioQueue.push({
                    id: Date.now() + Math.random(),
                    name: selectedOption.text(),
                    code: selectedOption.data('value'),
                    group: selectedOption.data('group'),
                });
                this.saveAndRender();
            });

            this.runScenarioButton.on('click', ()=> {
                this.runTasks(this.scenarioQueue.map(task => task.code));
            });

            this.clearQueueButton.on('click', () => {
                this.scenarioQueue = [];
                this.saveAndRender();
            })

            this.queueList.on('click', '.remove', (e)=>  {
                const id = $(e.currentTarget).data('id');
                this.scenarioQueue = this.scenarioQueue.filter(t => t.id !== id);
                this.saveAndRender();
            });
        },

        initDragAndDropEvents: function () {
            let draggedId = null;

            $(document).on('dragstart', '.queue-item', (e) => {
                draggedId = $(e.currentTarget).data('id');
                $(e.currentTarget).addClass('dragging');
                this.queueList.addClass('queue-list-dragging');
            });

            $(document).on('dragover', '.queue-item', (e) => {
                e.preventDefault();
                const target = e.currentTarget

                const rect = target.getBoundingClientRect();
                const relY = e.originalEvent.clientY - rect.top;
                const height = rect.height;

                $('.queue-item').removeClass('drop-before drop-after');
                $(target).addClass((relY < height / 2) ? 'drop-before' : 'drop-after');
            });

            $(document).on('dragleave', '.queue-item', (e)=> {
                $(e.currentTarget).removeClass('drop-before drop-after');
            });

            $(document).on('drop', '.queue-item', (e) => {
                e.preventDefault();
                const targetId = $(e.currentTarget).data('id');
                const isAfter = $(e.currentTarget).hasClass('drop-after');

                $('.queue-item').removeClass('drop-before drop-after dragging');
                this.queueList.removeClass('queue-list-dragging');

                if (draggedId === targetId) return;

                const fromIdx = this.scenarioQueue.findIndex(t => t.id === draggedId);
                let toIdx = this.scenarioQueue.findIndex(t => t.id === targetId);

                if (isAfter) toIdx++;
                const [item] = this.scenarioQueue.splice(fromIdx, 1);
                if (fromIdx < toIdx) toIdx--;

                this.scenarioQueue.splice(toIdx, 0, item);
                this.saveAndRender();
            });

            $(document).on('dragend', '.queue-item',  () => {
                $('.queue-item').removeClass('dragging drop-before drop-after');
                this.queueList.removeClass('queue-list-dragging');
            });
        },

        initDropDownEvents: function () {
            this.dropDownTrigger.on('click', (e) => {
                e.stopPropagation();
                this.dropDownMenu.toggle();
            });

            this.dropDownMenu.on('click', '.dropdown-item', (e) => {
                const target = $(e.currentTarget);

                $('.dropdown-item').removeClass('selected');
                target.addClass('selected');
                this.dropDownTriggerText.text(target.text());

                this.addToSequenceButton.prop('disabled', !target.data('value'));

                this.dropDownMenu.hide();
            });

            this.dropDownMenu.on('click', '.run-group-btn', (e) => {
                e.stopPropagation();
                const groupName = $(e.currentTarget).data('group');

                const tasks = this.dropDownMenu
                        .find(`.dropdown-item[data-group=${groupName}]`)
                        .map((index, value) => $(value).data('value'))
                        .get();

                this.runTasks(tasks);

                this.dropDownMenu.hide();
            });

            $(document).on('click', () => {
                this.dropDownMenu.hide();
            });
        },

        runTasks: function (tasks) {
            tasks = tasks.filter(item => item !== '');

            const pageContainer = $('.cron-tab-container');
            pageContainer.addClass('loading');

            $('#execution-results').text('');
            $.ajax({
                url: this.cronRunUrl,
                type: 'POST',
                dataType: 'text',
                data: {
                    form_key: FORM_KEY,
                    task_codes: tasks
                },
                success: (response) => {
                    $('#execution-results').text(response)
                },
                complete: () => {
                    $('.cron-tab-container').removeClass('loading')
                }
            });
        },

        loadScenarioQueue: function () {
            const storedQueue = localStorage.getItem(this.localstorageKey);
            if (storedQueue) {
                this.scenarioQueue = JSON.parse(storedQueue);
            }

            this.renderQueue();
        },

        saveAndRender: function () {
            localStorage.setItem(this.localstorageKey, JSON.stringify(this.scenarioQueue));
            this.renderQueue();
        },

        renderQueue: function () {
            this.queueList.empty();

            if (this.scenarioQueue.length === 0) {
                this.clearQueueButton.prop('disabled', true);
                this.runScenarioButton.prop('disabled', true);

                const emptyText = `
                        <div class="empty-state">
                            Queue is empty. Select a task and click "Add to Sequence" to build a sequence.
                        </div>`
                this.queueList.append(emptyText);

                return;
            }

            this.clearQueueButton.prop('disabled', false);
            this.runScenarioButton.prop('disabled', false);

            this.scenarioQueue.forEach((task, index) => {
                const li = $(`
                        <li class="queue-item" draggable="true" data-id="${task.id}">
                            <span class="index">${index + 1}.</span>
                            <span class="name" title="${task.name}">
                                <span class="group">[${task.group}]</span> ${task.name}
                            </span>
                            <span class="remove" data-id="${task.id}" title="Remove">&times;</span>
                        </li>
                    `);
                this.queueList.append(li);
            });
        },
    })

    return (options) => new window.CronTab(options);
});
