$(document).ready(function () {
    var $hasCron = $('#project_hasCron');
    $hasCron.change(function() {
        var $form = $(this).closest('form'),
            $cronFreq = $('.hasCron-group', $form),
            $cronFreqLabel = $('.hasCron-group label', $form);
        if($(this).prop('checked')) {
            $cronFreq.removeClass('d-none');
            $cronFreqLabel.addClass('required');
        }
        else {
            $cronFreq.addClass('d-none');
            $cronFreqLabel.removeClass('required');
        }
    });

    var $gitRemoteRepository = $('#project_gitRemoteRepository');
    $gitRemoteRepository.blur(function() {
        $.ajax({
            url : '/project/ajax/git-branches',
            type: 'POST',
            data : {gitRemoteRepository: $gitRemoteRepository.val()},
            success: function(data) {
                var $gitBranches = $('#project_gitBranch');
                $gitBranches.empty(); // remove old options
                $.each(data, function(key,value) {
                    $gitBranches.append($("<option></option>")
                        .attr("value", value).text(key));
                });
            }
        });
    });
});