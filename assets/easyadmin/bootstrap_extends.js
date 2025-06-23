import $ from 'jquery';
window.$ = window.jQuery = $;
import 'bootstrap-table';
import 'bootstrap-table/dist/bootstrap-table.min.css';
import 'bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.js';

(function ($) {
    let defaultSearchStates = {},
        filterSelector = $('.filter-checkbox');
    $.each(filterSelector, function(i,elem){
        const tableId = $(this).parents('.filter-wrapper').data('tableId');
        if (!defaultSearchStates.hasOwnProperty(tableId)) {
            defaultSearchStates[tableId] = [];
        }
        defaultSearchStates[tableId].push($(this).val());
    });

    filterSelector.on('change', function(e){
        const tableId = $(this).parents('.filter-wrapper').data('tableId'),
            dataTableElem = $('table#' + tableId);
        if (dataTableElem.length !== 1) {
            return;
        }
        let searchStates = [];
        $.each($('.filter-checkbox:checked'), function(i,elem){
            searchStates.push($(this).val())
        });

        if (searchStates.length === 0) {
            searchStates = defaultSearchStates[tableId];
        }
        dataTableElem.bootstrapTable('filterBy', {
            state: searchStates
        });
    });
})(jQuery);
